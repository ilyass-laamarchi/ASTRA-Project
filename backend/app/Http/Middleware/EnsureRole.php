<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Rejects inactive accounts and enforces a comma-separated list of roles. */
class EnsureRole
{
    /** Rejects missing, inactive, or wrong-role users before a protected controller runs. */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user || ! $user->is_active || ! in_array($user->role, $roles, true)) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}
