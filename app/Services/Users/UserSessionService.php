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
use Illuminate\Session\SessionManager;

class UserSessionService
{
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
     */
    public function touchFromRequest(Request $request): void
    {
        $user = $request->user();
        if (!$user instanceof User || !$request->hasSession()) {
            return;
        }

        $token = $user->currentAccessToken();
        if ($token instanceof ApiKey) {
            return;
        }

        $this->upsert($user, $request->session()->getId(), $request->ip(), $request->userAgent());
    }

    /**
     * Persist a tracked session after a successful interactive login.
     */
    public function createFromRequest(User $user, Request $request): UserSession
    {
        return $this->upsert($user, $request->session()->getId(), $request->ip(), $request->userAgent());
    }

    /**
     * Revoke a single tracked session and destroy the underlying Laravel session.
     */
    public function revoke(User $user, UserSession $session): void
    {
        if ($session->user_id !== $user->id) {
            return;
        }

        $sessionId = $session->session_id;
        $session->delete();
        $this->destroyLaravelSession($sessionId);
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
            $this->revoke($user, $session);
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
            $this->revoke($user, $session);
        }
    }

    /**
     * Drop the tracking row for a Laravel session id without destroying the store entry.
     * Useful during normal logout where the session is invalidated separately.
     */
    public function forgetBySessionId(string $sessionId): void
    {
        UserSession::query()->where('session_id', $sessionId)->delete();
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

        if (config('session.driver') !== 'database') {
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
}
