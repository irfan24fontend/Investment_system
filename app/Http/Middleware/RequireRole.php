<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class RequireRole {
    public function handle(Request $request, Closure $next, string ...$roles) {
        $u = $request->user();
        abort_unless($u && ($u->role === 'super_admin' || in_array($u->role, $roles, true)), 403);
        return $next($request);
    }
}
