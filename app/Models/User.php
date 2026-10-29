<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // role & verification fields are NOT mass-assignable (mass-assignment protection)
    protected $fillable = ['name', 'email', 'password', 'phone', 'nid_number'];
    protected $hidden = ['password', 'remember_token', 'nid_number'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed',
            'investor_verified_at' => 'datetime', 'payout_account_verified_at' => 'datetime'];
    }

    public function hasRole(string ...$roles): bool { return in_array($this->role, $roles, true); }
    public function isStaff(): bool { return $this->role !== 'investor'; }
}
