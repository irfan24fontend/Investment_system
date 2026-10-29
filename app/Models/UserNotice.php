<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UserNotice extends Model {
    protected $guarded = ['id']; protected $casts = ['read_at' => 'datetime'];
    public static function to(int $userId, string $title, ?string $body = null): void {
        static::create(['user_id' => $userId, 'title' => $title, 'body' => $body]);
    }
}
