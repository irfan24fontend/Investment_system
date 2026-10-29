<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    /** Set SEED_STAFF_PASSWORD in .env (min 12 chars). No default password exists. */
    public function run(): void {
        $pw = env('SEED_STAFF_PASSWORD');
        if (! $pw || strlen($pw) < 12) throw new \RuntimeException('Set SEED_STAFF_PASSWORD (12+ chars) in .env first.');
        foreach (['super_admin' => 'Super Admin', 'finance_manager' => 'Finance Manager', 'finance_manager_2' => 'Finance Approver', 'order_manager' => 'Order Manager'] as $role => $name) {
            $u = User::firstOrNew(['email' => "$role@example.test"]);
            $u->fill(['name' => $name, 'password' => $pw]);
            $u->forceFill(['role' => str_starts_with($role, 'finance') ? 'finance_manager' : $role, 'investor_verified_at' => null])->save();
        }
    }
}
