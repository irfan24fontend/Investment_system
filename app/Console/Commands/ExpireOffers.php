<?php
namespace App\Console\Commands;
use App\Models\InvestmentOffer;
use App\Models\User;
use App\Models\UserNotice;
use Illuminate\Console\Command;

class ExpireOffers extends Command {
    protected $signature = 'offers:expire';
    protected $description = 'Mark unfunded offers past their deadline as expired (backend also enforces deadline on every request)';
    public function handle(): int {
        $offers = InvestmentOffer::where('status', 'published')->where('deadline_at', '<=', now())->whereDoesntHave('investment')->get();
        foreach ($offers as $o) {
            $o->forceFill(['status' => 'expired'])->save();
            User::where('role', 'super_admin')->each(fn ($a) => UserNotice::to($a->id, "Offer {$o->code} expired", 'No investor funded it before the deadline.'));
        }
        $this->info($offers->count().' offers expired.');
        return 0;
    }
}
