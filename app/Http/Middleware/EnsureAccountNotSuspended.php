<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureAccountNotSuspended
{
    /**
     * Block suspended accounts from using the panel and force a logout.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        if ($user && $user->suspended) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                throw new AccessDeniedHttpException(trans('auth.account_suspended'));
            }

            return redirect()->route('auth.login')
                ->withErrors(['user' => trans('auth.account_suspended')]);
        }

        return $next($request);
    }
}
