<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
    protected $guarded = ['id', 'status', 'offer_id', 'created_by'];
    protected $casts = ['delivered_at' => 'datetime', 'expected_delivery_date' => 'date'];
    public function offer() { return $this->belongsTo(InvestmentOffer::class, 'offer_id'); }
}
