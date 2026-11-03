<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean'];

    public function orderItems()
    {
        return $this->hasMany(CustomerOrderItem::class);
    }
}
