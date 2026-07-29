<?php

namespace Pterodactyl\Services\Users;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\ApiKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\UserSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Session\SessionManager;

class UserSessionService
{
    private const REVOKED_CACHE_PREFIX = 'user_session:revoked:';

    public function __construct(private SessionManager $session)
    {
    }

    /**
     * Return active tracked sessions for a user, pruning expired rows first.
     *
     * @return \Illuminate\Support\Collection<int, \Pterodactyl\Models\UserSession>
     */
    public function getActiveForUser(User $user): Collection
    {
        $this->pruneExpired($user);

        return $user->sessions()->orderByDesc('last_used_at')->get();
    }

    /**
     * Create or refresh a tracked browser session for the authenticated user.
     *
     * Returns false when the underlying Laravel session was previously revoked
     * and the caller should treat the request as unauthenticated.
     */
    public function touchFromRequest(Request $request): bool
    {
        $user = $request->user();
        if (!$user instanceof User || !$request->hasSession()) {
            return true;
        }

        $token = $user->currentAccessToken();
        if ($token instanceof ApiKey) {
            return true;
        }

        $sessionId = $request->session()->getId();
        if ($this->isRevoked($sessionId)) {
            $this->forceLogout($request);

            return false;
        }

        $this->upsert($user, $sessionId, $request->ip(), $request->userAgent());

        return true;
    }

    /**
     * Persist a tracked session after a successful interactive login.
     */
    public function createFromRequest(User $user, Request $request): UserSession
    {
        $sessionId = $request->session()->getId();
        $this->clearRevoked($sessionId);

        return $this->upsert($user, $sessionId, $request->ip(), $request->userAgent());
    }

    /**
     * Revoke a single tracked session and destroy the underlying Laravel session.
     */
    public function revoke(User $user, UserSession $session, bool $invalidateRememberToken = true): void
    {
        if ($session->user_id !== $user->id) {
            return;
        }

        $sessionId = $session->session_id;
        $session->delete();
        $this->markRevoked($sessionId);
        $this->destroyLaravelSession($sessionId);

        if ($invalidateRememberToken) {
            $this->invalidateRememberToken($user);
        }
    }

    /**
     * Revoke every tracked session for the user except the current browser session.
     */
    public function revokeOthers(User $user, string $currentSessionId): int
    {
        $sessions = $user->sessions()
            ->where('session_id', '!=', $currentSessionId)
            ->get();

        foreach ($sessions as $session) {
            $this->revoke($user, $session, false);
        }

        if ($sessions->isNotEmpty()) {
            $this->invalidateRememberToken($user);
        }

        return $sessions->count();
    }

    /**
     * Revoke every tracked session for the user.
     */
    public function revokeAll(User $user): void
    {
        $sessions = $user->sessions()->get();

        foreach ($sessions as $session) {
            $this->revoke($user, $session, false);
        }

        if ($sessions->isNotEmpty()) {
            $this->invalidateRememberToken($user, refreshCurrentRecaller: false);
        }
    }

    /**
     * Drop the tracking row for a Laravel session id without destroying the store entry.
     * Useful during normal logout where the session is invalidated separately.
     */
    public function forgetBySessionId(string $sessionId): void
    {
        UserSession::query()->where('session_id', $sessionId)->delete();
        $this->markRevoked($sessionId);
    }

    public function isRevoked(string $sessionId): bool
    {
        return Cache::has(self::REVOKED_CACHE_PREFIX . $sessionId);
    }

    private function upsert(User $user, string $sessionId, ?string $ipAddress, ?string $userAgent): UserSession
    {
        /** @var UserSession $session */
        $session = UserSession::query()->firstOrNew(['session_id' => $sessionId]);

        if (!$session->exists) {
            $session->uuid = Str::uuid()->toString();
        }

        $session->forceFill([
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent !== null ? Str::limit($userAgent, 512, '') : null,
            'last_used_at' => now(),
        ])->saveOrFail();

        return $session->refresh();
    }

    private function pruneExpired(User $user): void
    {
        $lifetime = (int) config('session.lifetime', 120);

        $user->sessions()
            ->where('last_used_at', '<', now()->subMinutes(max($lifetime, 1)))
            ->delete();
    }

    private function destroyLaravelSession(string $sessionId): void
    {
        try {
            $this->session->getHandler()->destroy($sessionId);
        } catch (\Throwable $exception) {
            Log::warning('Failed to destroy Laravel session while revoking a user session.', [
                'session_id' => $sessionId,
                'exception' => $exception->getMessage(),
            ]);
        }

        // Cache-backed drivers (redis/memcached/dynamodb) store the payload under the
        // raw session id. Forget it explicitly in case the handler instance above was
        // not the same store the SPA writes to.
        $driver = config('session.driver');
        if (in_array($driver, ['redis', 'memcached', 'dynamodb', 'apc'], true)) {
            try {
                $store = config('session.store') ?: $driver;
                Cache::store($store)->forget($sessionId);
            } catch (\Throwable $exception) {
                Log::warning('Failed to forget cache-backed session while revoking a user session.', [
                    'session_id' => $sessionId,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        if ($driver !== 'database') {
            return;
        }

        try {
            DB::table(config('session.table', 'sessions'))
                ->where('id', $sessionId)
                ->delete();
        } catch (\Throwable $exception) {
            Log::warning('Failed to delete database session row while revoking a user session.', [
                'session_id' => $sessionId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Remember-me cookies survive session store deletion. Cycle the token so a
     * revoked device cannot silently re-authenticate and reappear in the list.
     *
     * The browser performing the revoke keeps its active session, so it remains
     * signed in even though its remember cookie becomes stale.
     */
    private function invalidateRememberToken(User $user, bool $refreshCurrentRecaller = true): void
    {
        $user->setRememberToken(Str::random(60));
        $user->save();

        if (!$refreshCurrentRecaller) {
            return;
        }

        // Re-issue the recaller only when the session guard is available (web UI).
        // Sanctum's RequestGuard used in some API test paths has no login() method.
        $guard = Auth::guard('web');
        if (
            method_exists($guard, 'login')
            && $guard->check()
            && (int) $guard->id() === (int) $user->getAuthIdentifier()
        ) {
            $guard->login($user, true);
        }
    }

    private function markRevoked(string $sessionId): void
    {
        $lifetime = max((int) config('session.lifetime', 120), 1);

        Cache::put(
            self::REVOKED_CACHE_PREFIX . $sessionId,
            true,
            now()->addMinutes($lifetime)
        );
    }

    private function clearRevoked(string $sessionId): void
    {
        Cache::forget(self::REVOKED_CACHE_PREFIX . $sessionId);
    }

    private function forceLogout(Request $request): void
    {
        try {
            Auth::guard()->logout();
        } catch (\Throwable) {
            // Guard may already be unauthenticated.
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
