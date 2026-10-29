<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Settlement extends Model {
    protected $guarded = ['id']; protected $casts = ['breakdown' => 'array'];
    public function investment() { return $this->belongsTo(Investment::class); }
    public function order() { return $this->belongsTo(Order::class); }
}
