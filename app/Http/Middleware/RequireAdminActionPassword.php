<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminActionPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Admin access only.');
        }

        if (str_starts_with($request->path(), 'admin/users/') && ! $user->isSuperAdmin()) {
            abort(403, 'Head admin access only.');
        }

        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $password = $request->input('current_password');
        if (! is_string($password) || ! Hash::check($password, $user->password)) {
            $message = 'Enter your current password to confirm this admin action.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['current_password' => [$message]],
                ], 422);
            }

            return back()
                ->withErrors(['current_password' => $message])
                ->withInput($request->except('current_password'));
        }

        return $next($request);
    }
}
