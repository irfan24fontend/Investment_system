<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['agreement_snapshot' => 'array', 'confirmed_at' => 'datetime'];
    public function offer() { return $this->belongsTo(InvestmentOffer::class, 'offer_id'); }
    public function settlement() { return $this->hasOne(Settlement::class); }
    public function user() { return $this->belongsTo(User::class); }
}
