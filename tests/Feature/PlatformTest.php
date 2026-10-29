<?php
namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Models\{Investment, InvestmentOffer, Order, User};
use App\Services\{DepositService, InvestmentService, LedgerService, SettlementService, WithdrawalService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'investor', bool $verified = true): User {
        $u = User::factory()->create();
        $u->forceFill(['role' => $role, 'investor_verified_at' => $verified ? now() : null,
            'payout_account' => '01712345678', 'payout_account_verified_at' => $verified ? now() : null])->save();
        return $u;
    }
    private function funded(string $amt = '10000.00'): User {
        $u = $this->user(); app(LedgerService::class)->creditDeposit($u->id, $amt, 'd-'.$u->id); return $u;
    }
    private function wallet(User $u): string { return app(LedgerService::class)->account($u->id, 'wallet')->balance(); }
    private function offer(): InvestmentOffer {
        return InvestmentOffer::forceCreate(['code' => 'O'.uniqid(), 'title' => 'T', 'product_name' => 'P', 'quantity' => 1000, 'unit_price' => 10,
            'total_cost' => 10000, 'required_amount' => 10000, 'risk_disclosure' => 'Loss possible', 'deadline_at' => now()->addDay(), 'status' => 'published',
            'settlement_rules' => ['investor_profit_share_pct' => 50], 'created_by' => $this->user('super_admin')->id]);
    }
    /** @return array{Investment,Order,User} */
    private function fundedInvestment(string $orderStatus = 'financial_verification'): array {
        $o = $this->offer(); $inv = $this->funded();
        $i = app(InvestmentService::class)->invest($inv, $o->id, 'k');
        $ord = Order::forceCreate(['code' => 'ORD'.uniqid(), 'product_name' => 'P', 'quantity' => 1000, 'purchase_cost' => 8000, 'transport_cost' => 500,
            'status' => $orderStatus, 'offer_id' => $o->id, 'created_by' => $this->user('order_manager')->id]);
        return [$i, $ord, $inv];
    }
    private function settle(Investment $i, string $rev) {
        $s = app(SettlementService::class); $a = $this->user('finance_manager'); $b = $this->user('finance_manager');
        $st = $s->prepare($i, $a, $rev); $s->approve($st, $b); return $st->fresh();
    }

    public function test_profit_settlement(): void {
        [$i, , $u] = $this->fundedInvestment();
        $st = $this->settle($i, '12000.00');
        $this->assertEquals('3500.00', $st->net_result);
        $this->assertSame('11750.00', $this->wallet($u));
        $this->assertSame('settled', $i->fresh()->status);
        $this->assertSame('0.00', app(LedgerService::class)->reconcile());
    }

    public function test_loss_making_project(): void {
        [$i, , $u] = $this->fundedInvestment();
        $st = $this->settle($i, '6000.00');
        $this->assertEquals('-2500.00', $st->net_result);
        $this->assertSame('7500.00', $this->wallet($u));
        $this->assertSame('loss_settled', $i->fresh()->status);
        $this->assertSame('0.00', app(LedgerService::class)->reconcile());
    }

    public function test_cancelled_order_settlement_is_loss_not_profit(): void {
        [$i, , $u] = $this->fundedInvestment('cancelled');
        $this->settle($i, '0.00');
        $this->assertSame('1500.00', $this->wallet($u)); // 8,500 costs already spent, nothing received
        $this->assertSame('loss_settled', $i->fresh()->status);
    }

    public function test_duplicate_settlement_and_self_approval_blocked(): void {
        [$i] = $this->fundedInvestment();
        $s = app(SettlementService::class); $a = $this->user('finance_manager');
        $st = $s->prepare($i, $a, '12000.00');
        try { $s->approve($st, $a); $this->fail('self approve'); } catch (BusinessRuleException) {}
        $b = $this->user('finance_manager'); $s->approve($st, $b);
        try { $s->approve($st, $b); $this->fail('double approve'); } catch (BusinessRuleException) {}
        try { $s->prepare($i, $a, '12000.00'); $this->fail('second prepare'); } catch (BusinessRuleException) {}
    }

    public function test_order_not_ready_cannot_settle(): void {
        [$i] = $this->fundedInvestment('procurement');
        $this->expectException(BusinessRuleException::class);
        app(SettlementService::class)->prepare($i, $this->user('finance_manager'), '1000.00');
    }

    public function test_withdrawal_limits_reserve_and_release(): void {
        $u = $this->funded('1000.00'); $svc = app(WithdrawalService::class);
        try { $svc->request($u, '2000.00', 'a'); $this->fail('over balance'); } catch (BusinessRuleException) {}
        $w = $svc->request($u, '400.00', 'b');
        $this->assertSame('600.00', $this->wallet($u));
        $this->assertSame($w->id, $svc->request($u, '400.00', 'b')->id);        // idempotent
        $this->assertSame('600.00', $this->wallet($u));
        $svc->reject($w, $this->user('finance_manager'), 'test');
        $this->assertSame('1000.00', $this->wallet($u));
        $w2 = $svc->request($u, '300.00', 'c');
        $svc->complete($w2, $this->user('finance_manager'), 'BK-PAY-1');
        $this->assertSame('700.00', $this->wallet($u));
        $this->assertSame('0.00', app(LedgerService::class)->reconcile());
    }

    public function test_unverified_cannot_withdraw_or_invest(): void {
        $u = $this->user('investor', false); app(LedgerService::class)->creditDeposit($u->id, '5000.00', 'x');
        $this->expectException(BusinessRuleException::class);
        app(WithdrawalService::class)->request($u, '200.00', 'k');
    }

    public function test_failed_investment_rolls_back(): void {
        $o = $this->offer(); $u = $this->funded('100.00');
        try { app(InvestmentService::class)->invest($u, $o->id, 'k'); } catch (BusinessRuleException) {}
        $this->assertSame(0, Investment::count());
        $this->assertSame('published', $o->fresh()->status);
        $this->assertSame('100.00', $this->wallet($u));
    }

    public function test_manual_bkash_needs_approval_and_credits_once(): void {
        $u = $this->user(); $svc = app(DepositService::class); $admin = $this->user('finance_manager');
        $d = $svc->submitBkash($u, '500.00', 'ab12cd34ef', '01712345678');
        $this->assertSame('0.00', $this->wallet($u));             // not credited before approval
        try { $svc->submitBkash($this->user(), '500.00', 'AB12CD34EF', '01812345678'); $this->fail('dup trx'); } catch (BusinessRuleException) {}
        $svc->approve($d, $admin);
        $this->assertSame('500.00', $this->wallet($u));
        try { $svc->approve($d, $admin); $this->fail('double approve'); } catch (BusinessRuleException) {}
        $this->assertSame('500.00', $this->wallet($u));
        $this->assertSame('0.00', app(LedgerService::class)->reconcile());
    }

    public function test_rejected_bkash_deposit_not_credited(): void {
        $u = $this->user(); $svc = app(DepositService::class);
        $d = $svc->submitBkash($u, '300.00', 'ZZ99YY88XX', '01712345678');
        $svc->reject($d, $this->user('finance_manager'), 'TrxID not found');
        $this->assertSame('0.00', $this->wallet($u));
        $this->expectException(BusinessRuleException::class);
        $svc->approve($d, $this->user('finance_manager'));
    }

    public function test_role_and_idor_protection(): void {
        $a = $this->funded(); $b = $this->user();
        $this->actingAs($a)->get('/admin/offers')->assertForbidden();
        $this->actingAs($this->user('finance_manager'))->post('/admin/offers', [])->assertForbidden();
        $this->actingAs($this->user('order_manager'))->get('/admin/settlements')->assertForbidden();
        $this->actingAs($this->user('order_manager'))->get('/admin/deposits')->assertForbidden();
        $i = app(InvestmentService::class)->invest($a, $this->offer()->id, 'k');
        $this->actingAs($b)->get("/investments/{$i->id}")->assertNotFound();
        $this->actingAs($a)->get("/investments/{$i->id}")->assertOk();
    }

    public function test_ledger_reconciles_after_activity(): void {
        $this->fundedInvestment(); $this->assertSame('0.00', app(LedgerService::class)->reconcile());
    }

    public function test_super_admin_completes_offer_and_investor_gets_principal_plus_profit(): void {
        $o = $this->offer(); $u = $this->funded(); app(InvestmentService::class)->invest($u, $o->id, 'k');
        $st = app(SettlementService::class)->completeOffer($o, $this->user('super_admin'), '12000.00', '0.00', '8500.00');
        $this->assertEquals('3500.00', $st->net_result);
        $this->assertEquals('1750.00', $st->investor_profit);
        $this->assertSame('11750.00', $this->wallet($u));
        $this->assertSame('completed', $o->fresh()->status);
        $this->assertSame('0.00', app(LedgerService::class)->reconcile());
        try { app(SettlementService::class)->completeOffer($o, $this->user('super_admin'), '1.00', '0.00', '1.00'); $this->fail('double complete'); } catch (BusinessRuleException) {}
        $this->assertSame('11750.00', $this->wallet($u));
    }

    public function test_complete_offer_with_loss_and_role_restriction(): void {
        $o = $this->offer(); $u = $this->funded(); app(InvestmentService::class)->invest($u, $o->id, 'k');
        try { app(SettlementService::class)->completeOffer($o, $this->user('finance_manager'), '6000.00', '0.00', '8500.00'); $this->fail('only super admin'); } catch (BusinessRuleException) {}
        $st = app(SettlementService::class)->completeOffer($o, $this->user('super_admin'), '6000.00', '0.00', '8500.00');
        $this->assertEquals('2500.00', $st->investor_loss);
        $this->assertSame('7500.00', $this->wallet($u));
    }

    private function returnPctOffer(): InvestmentOffer {
        $o = $this->offer(); $o->forceFill(['settlement_rules' => ['investor_return_pct' => 10]])->save(); return $o;
    }

    public function test_fixed_percent_on_invested_amount_is_paid_when_profit_covers_it(): void {
        $o = $this->returnPctOffer(); $u = $this->funded(); app(InvestmentService::class)->invest($u, $o->id, 'k');
        $this->assertSame('1000.00', $o->fresh()->targetProfit());
        $st = app(SettlementService::class)->completeOffer($o, $this->user('super_admin'), '12000.00', '0.00', '8500.00');
        $this->assertEquals('1000.00', $st->investor_profit);   // 10% of 10,000, not 10% of net 3,500
        $this->assertEquals('11000.00', $this->wallet($u));
        $this->assertSame('0.00', app(LedgerService::class)->reconcile());
    }

    public function test_return_is_capped_by_actual_profit(): void {
        $o = $this->returnPctOffer(); $u = $this->funded(); app(InvestmentService::class)->invest($u, $o->id, 'k');
        $st = app(SettlementService::class)->completeOffer($o, $this->user('super_admin'), '9000.00', '0.00', '8500.00');
        $this->assertEquals('500.00', $st->investor_profit);    // project earned only 500, target was 1000
        $this->assertEquals('10500.00', $this->wallet($u));
    }

    public function test_recalculate_fixes_wrong_numbers_before_approval_only(): void {
        [$i, , $u] = $this->fundedInvestment();
        $svc = app(SettlementService::class); $a = $this->user('finance_manager'); $b = $this->user('finance_manager');
        $st = $svc->prepare($i, $a, '1000.00');                        // wrong revenue entered
        $this->assertEquals('0.00', $st->investor_profit);
        $st = $svc->recalculate($st, $b, '12000.00');                  // corrected
        $this->assertEquals('3500.00', $st->net_result);
        $this->assertEquals('11750.00', $st->payout);
        $this->assertSame($b->id, $st->prepared_by);
        $this->assertSame(1, \App\Models\Settlement::count());       // same row, no duplicate
        $this->assertEquals('0.00', $this->wallet($u));                // nothing credited yet
        $svc->approve($st, $a);
        $this->assertEquals('11750.00', $this->wallet($u));
        $this->expectException(BusinessRuleException::class);          // posted = final
        $svc->recalculate($st->fresh(), $b, '5000.00');
    }

    public function test_islami_bank_deposit_needs_approval_and_unique_reference(): void {
        $u = $this->user(); $svc = app(DepositService::class);
        $d = $svc->submitBank($u, '2000.00', 'ib-778899', '2050123456789', 'receipts/x.jpg');
        $this->assertSame('0.00', $this->wallet($u));
        try { $svc->submitBank($this->user(), '2000.00', 'IB-778899', null, 'receipts/y.jpg'); $this->fail('dup ref'); } catch (BusinessRuleException) {}
        $svc->approve($d, $this->user('finance_manager'));
        $this->assertSame('2000.00', $this->wallet($u));
    }

    public function test_withdrawal_to_any_bank_is_stored_and_reserved(): void {
        $u = $this->funded('5000.00');
        $w = app(WithdrawalService::class)->request($u, '1000.00', 'w1', ['method' => 'bank', 'bank_name' => 'Dutch-Bangla Bank', 'account_name' => 'Rahim', 'account_number' => '1234567890']);
        $this->assertSame('Dutch-Bangla Bank', $w->bank_name);
        $this->assertSame('4000.00', $this->wallet($u));
    }
}
