<?php
namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Models\InvestmentOffer;
use App\Models\User;
use App\Services\InvestmentService;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleInvestorTest extends TestCase
{
    use RefreshDatabase;

    private function offer(array $o = []): InvestmentOffer
    {
        $admin = User::factory()->create();
        return InvestmentOffer::forceCreate(array_merge([
            'code' => 'OFR-'.uniqid(), 'title' => 'T', 'product_name' => 'PET Bottle', 'quantity' => 1000,
            'unit_price' => 10, 'total_cost' => 10000, 'required_amount' => 10000, 'risk_disclosure' => 'Loss possible.',
            'deadline_at' => now()->addDay(), 'status' => 'published', 'created_by' => $admin->id,
        ], $o));
    }

    private function funded(string $amt = '10000.00'): User
    {
        $u = User::factory()->create();
        $u->forceFill(['investor_verified_at' => now()])->save();
        app(LedgerService::class)->creditDeposit($u->id, $amt, 'dep-'.$u->id);
        return $u;
    }

    public function test_second_investor_rejected_and_offer_hidden(): void
    {
        $o = $this->offer(); $a = $this->funded(); $b = $this->funded();
        $svc = app(InvestmentService::class);
        $svc->invest($a, $o->id, 'k1');
        $this->assertCount(0, InvestmentOffer::available()->get());
        $this->assertSame('funded', $o->fresh()->status); // regression: status is guarded, must be forceFill'ed
        try { $svc->invest($b, $o->id, 'k2'); $this->fail('Second investor must be rejected'); }
        catch (BusinessRuleException) {}
        $this->assertSame('10000.00', app(LedgerService::class)->account($b->id, 'wallet')->balance());
    }

    public function test_expired_offer_rejected(): void
    {
        $o = $this->offer(['deadline_at' => now()->subMinute()]);
        $this->expectException(BusinessRuleException::class);
        app(InvestmentService::class)->invest($this->funded(), $o->id, 'k');
    }

    public function test_insufficient_balance(): void
    {
        $o = $this->offer();
        $this->expectException(BusinessRuleException::class);
        app(InvestmentService::class)->invest($this->funded('500.00'), $o->id, 'k');
    }

    public function test_same_idempotency_key_does_not_double_debit(): void
    {
        $o = $this->offer(); $a = $this->funded('20000.00');
        $svc = app(InvestmentService::class);
        $i1 = $svc->invest($a, $o->id, 'same'); $i2 = $svc->invest($a, $o->id, 'same');
        $this->assertSame($i1->id, $i2->id);
        $this->assertSame('10000.00', app(LedgerService::class)->account($a->id, 'wallet')->balance());
    }

    public function test_duplicate_deposit_callback_credits_once(): void
    {
        $u = User::factory()->create(); $l = app(LedgerService::class);
        $l->creditDeposit($u->id, '1000.00', 'BK123'); $l->creditDeposit($u->id, '1000.00', 'BK123');
        $this->assertSame('1000.00', $l->account($u->id, 'wallet')->balance());
        $this->assertSame('0.00', $l->reconcile());
    }
}
