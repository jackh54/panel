<?php

namespace Pterodactyl\Http\Middleware;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\ApiKey;
use Illuminate\Support\Facades\Auth;
use Pterodactyl\Services\Users\UserSessionService;

class TrackUserSession
{
    public function __construct(private UserSessionService $service)
    {
    }

    /**
     * Keep a durable record of browser sessions so account holders can review
     * and revoke devices even when Laravel stores sessions in Redis or files.
     *
     * Revoked session ids are rejected before the request is handled so a
     * destroyed (or remember-me resurrected) session cannot keep acting as the user.
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        if ($this->rejectIfRevoked($request)) {
            return response()->json([
                'errors' => [[
                    'code' => 'AuthenticationException',
                    'status' => '401',
                    'detail' => 'Unauthenticated.',
                ]],
            ], 401);
        }

        $response = $next($request);

        try {
            $this->service->touchFromRequest($request);
        } catch (\Throwable) {
            // Session tracking must never break authenticated requests.
        }

        return $response;
    }

    private function rejectIfRevoked(Request $request): bool
    {
        $user = $request->user();
        if (!$user instanceof User || !$request->hasSession()) {
            return false;
        }

        $token = $user->currentAccessToken();
        if ($token instanceof ApiKey) {
            return false;
        }

        $sessionId = $request->session()->getId();
        if (!$this->service->isRevoked($sessionId)) {
            return false;
        }

        try {
            Auth::guard()->logout();
        } catch (\Throwable) {
            // Guard may already be unauthenticated.
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return true;
    }
}
