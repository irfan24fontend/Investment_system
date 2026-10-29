<?php
namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Models\Investment;
use App\Models\InvestmentOffer;
use App\Models\LedgerAccount;
use App\Models\PaymentDeposit;
use App\Models\UserNotice;
use App\Models\WithdrawalRequest;
use App\Services\DepositService;
use App\Services\InvestmentService;
use App\Services\LedgerService;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestorController extends Controller {
    public function __construct(private LedgerService $ledger) {}

    private function bal(string $type): string { return $this->ledger->account(auth()->id(), $type)->balance(); }

    public function dashboard() {
        $u = auth()->user();
        if ($u->isStaff()) return redirect()->route('admin.dashboard');
        $inv = Investment::where('user_id', $u->id);
        return view('investor.dashboard', [
            'available' => $this->bal('wallet'), 'invested' => $this->bal('invested'), 'reservedWd' => $this->bal('withdrawal_reserved'),
            'pendingDeposits' => PaymentDeposit::where('user_id', $u->id)->where('status', 'pending')->sum('amount'),
            'active' => (clone $inv)->where('status', 'confirmed')->count(),
            'completed' => (clone $inv)->whereIn('status', ['settled', 'loss_settled'])->count(),
            'offers' => InvestmentOffer::available()->orderBy('deadline_at')->limit(3)->get(),
            'realized' => \App\Models\Settlement::where('status', 'posted')->whereHas('investment', fn ($q) => $q->where('user_id', $u->id))->selectRaw('COALESCE(SUM(investor_profit),0) p, COALESCE(SUM(investor_loss),0) l')->first(),
            'recent' => Investment::with('offer')->where('user_id', $u->id)->latest()->limit(5)->get(),
        ]);
    }

    public function offers(Request $r) {
        $q = InvestmentOffer::available();
        if ($r->filled('category')) $q->where('category', $r->category);
        if ($r->filled('q')) $q->where('title', 'like', '%'.$r->q.'%');
        return view('investor.offers', ['offers' => $q->orderBy('deadline_at')->paginate(12)->withQueryString()]);
    }

    public function offer(InvestmentOffer $offer) {
        $mine = Investment::where('offer_id', $offer->id)->where('user_id', auth()->id())->first();
        abort_unless($mine || in_array($offer->status, ['published']), 404);
        return view('investor.offer', ['offer' => $offer, 'mine' => $mine, 'balance' => $this->bal('wallet'),
            'open' => InvestmentOffer::available()->whereKey($offer->id)->exists()]);
    }

    public function invest(Request $r, InvestmentOffer $offer, InvestmentService $svc) {
        $r->validate(['token' => 'required|string|max:60', 'agree' => 'accepted']);
        try { $inv = $svc->invest($r->user(), $offer->id, 'inv:'.$r->user()->id.':'.$r->token); }
        catch (BusinessRuleException $e) { return redirect()->route('offers')->withErrors(['invest' => $e->getMessage()]); }
        UserNotice::to($r->user()->id, 'Investment confirmed', "Offer {$offer->code} is now yours.");
        return redirect()->route('investments.show', $inv)->with('ok', 'Investment confirmed.');
    }

    public function investments() {
        return view('investor.investments', ['rows' => Investment::with('offer', 'settlement')->where('user_id', auth()->id())->latest()->paginate(15)]);
    }

    public function investment(Investment $investment) {
        abort_unless($investment->user_id === auth()->id(), 404); // IDOR guard
        return view('investor.investment', ['i' => $investment->load('offer', 'settlement'),
            'order' => \App\Models\Order::where('offer_id', $investment->offer_id)->first(['status', 'expected_delivery_date']),
            'updates' => DB::table('offer_progress')->where('offer_id', $investment->offer_id)->orderByDesc('id')->get()]);
    }

    /** Agreement + payment receipt. ?download=1 gives a .html file; otherwise open and use Print > Save as PDF. */
    public function receipt(Request $r, Investment $investment) {
        abort_unless($investment->user_id === auth()->id(), 404); // IDOR guard
        $html = view('investor.receipt', [
            'i' => $investment->load('offer', 'user', 'settlement'),
            'tx' => DB::table('ledger_transactions')->where('idempotency_key', "investment:{$investment->id}")->first(),
        ])->render();
        if ($r->boolean('download')) {
            return response($html)->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="agreement-INV-'.str_pad($investment->id, 6, '0', STR_PAD_LEFT).'.html"');
        }
        return response($html);
    }

    public function wallet() {
        $acc = $this->ledger->account(auth()->id(), 'wallet');
        $tx = DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->where('e.account_id', $acc->id)->orderByDesc('e.id')
            ->select('t.type', 't.reference', 'e.direction', 'e.amount', 'e.created_at')->paginate(20);
        return view('investor.wallet', ['balance' => $acc->balance(), 'reserved' => $this->bal('withdrawal_reserved'), 'tx' => $tx,
            'deposits' => PaymentDeposit::where('user_id', auth()->id())->latest()->limit(10)->get(),
            'withdrawals' => WithdrawalRequest::where('user_id', auth()->id())->latest()->limit(10)->get()]);
    }

    public function depositCash(Request $r, DepositService $svc) {
        $d = $r->validate(['amount' => 'required|numeric|min:100|max:10000000', 'reference' => 'required|max:100', 'receipt' => 'nullable|file|mimes:jpg,png,pdf|max:3072']);
        $path = $r->file('receipt')?->store('receipts'); // private disk (storage/app/private)
        try { $svc->submitCash($r->user(), number_format((float) $d['amount'], 2, '.', ''), $d['reference'], $path); }
        catch (BusinessRuleException $e) { return back()->withErrors(['reference' => $e->getMessage()]); }
        return back()->with('ok', 'Deposit submitted for verification.');
    }

    public function depositBkash(Request $r, DepositService $svc) {
        $d = $r->validate(['amount' => 'required|numeric|min:10|max:10000000', 'trx_id' => ['required', 'regex:/^[A-Za-z0-9]{8,14}$/'],
            'sender_number' => ['required', 'regex:/^01[3-9]\d{8}$/']]);
        try { $svc->submitBkash($r->user(), number_format((float) $d['amount'], 2, '.', ''), $d['trx_id'], $d['sender_number']); }
        catch (BusinessRuleException $e) { return back()->withErrors(['trx_id' => $e->getMessage()])->withInput(); }
        return back()->with('ok', 'bKash deposit submitted. It will be added after staff verification.');
    }

    public function depositBank(Request $r, DepositService $svc) {
        $d = $r->validate(['amount' => 'required|numeric|min:100|max:10000000', 'reference' => 'required|max:100',
            'sender_account' => 'nullable|max:20', 'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:3072']);
        $path = $r->file('receipt')->store('receipts'); // private disk
        try { $svc->submitBank($r->user(), number_format((float) $d['amount'], 2, '.', ''), $d['reference'], $d['sender_account'] ?? null, $path); }
        catch (BusinessRuleException $e) { return back()->withErrors(['reference' => $e->getMessage()])->withInput(); }
        return back()->with('ok', 'Bank deposit submitted. It will be added after staff verify the transfer.');
    }

    public function withdraw(Request $r, WithdrawalService $svc) {
        $d = $r->validate(['amount' => 'required|numeric|min:100', 'token' => 'required|max:60', 'method' => 'required|in:bank,bkash',
            'bank_name' => 'required_if:method,bank|nullable|max:100', 'account_name' => 'required|max:100',
            'account_number' => ['required', 'regex:/^[0-9\- ]{6,30}$/'], 'password' => 'required']);
        // re-confirm password: protects against someone using a logged-in session to pull money out
        if (! \Illuminate\Support\Facades\Hash::check($d['password'], $r->user()->password))
            return back()->withErrors(['password' => 'Wrong password.'])->withInput($r->except('password'));
        $dest = ['method' => $d['method'], 'bank_name' => $d['method'] === 'bank' ? $d['bank_name'] : null,
            'account_name' => $d['account_name'], 'account_number' => preg_replace('/\s+/', '', $d['account_number'])];
        try { $svc->request($r->user(), number_format((float) $d['amount'], 2, '.', ''), 'wd:'.$r->user()->id.':'.$d['token'], $dest); }
        catch (BusinessRuleException $e) { return back()->withErrors(['amount' => $e->getMessage()])->withInput($r->except('password')); }
        return back()->with('ok', 'Withdrawal requested. The amount is reserved until staff process it.');
    }

    public function notices() {
        $rows = UserNotice::where('user_id', auth()->id())->latest()->paginate(20);
        UserNotice::where('user_id', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);
        return view('investor.notices', compact('rows'));
    }
}
