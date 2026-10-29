<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class Audit {
    public static function log(string $action, $subject = null, array $meta = []): void {
        DB::table('audit_logs')->insert([
            'user_id' => auth()->id(), 'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null, 'subject_id' => $subject?->id,
            'meta' => $meta ? json_encode($meta) : null, 'ip' => request()?->ip(), 'created_at' => now(),
        ]);
    }
}
