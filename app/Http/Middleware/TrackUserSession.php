<?php

namespace Pterodactyl\Http\Middleware;

use Illuminate\Http\Request;
use Pterodactyl\Services\Users\UserSessionService;

class TrackUserSession
{
    public function __construct(private UserSessionService $service)
    {
    }

    /**
     * Keep a durable record of browser sessions so account holders can review
     * and revoke devices even when Laravel stores sessions in Redis or files.
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        $response = $next($request);

        try {
            $this->service->touchFromRequest($request);
        } catch (\Throwable) {
            // Session tracking must never break authenticated requests.
        }

        return $response;
    }
}
