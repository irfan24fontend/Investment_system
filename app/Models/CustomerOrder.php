<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(CustomerOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dueAmount(): string
    {
        return bcsub((string) $this->total_amount, (string) $this->paid_amount, 2);
    }
}
