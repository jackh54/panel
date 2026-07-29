<?php

namespace Pterodactyl\Services\Users;

use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Events\User\PasswordChanged;
use Pterodactyl\Notifications\AccountSuspended;
use Pterodactyl\Notifications\AccountUnsuspended;
use Pterodactyl\Services\Servers\SuspensionService;

class UserSuspensionService
{
    public function __construct(
        private SuspensionService $suspensionService,
        private UserSessionService $userSessionService,
    ) {
    }

    /**
     * Suspend a user account, cascade-suspend owned servers, revoke sessions/tokens.
     *
     * Side-effects after the account flag is saved (Wings sync, mail, SFTP revoke)
     * are isolated so they cannot turn a successful suspend into an HTTP 500.
     *
     * @throws \Throwable
     */
    public function suspend(User $user): void
    {
        if ($user->suspended) {
            return;
        }

        $servers = $user->servers()->get()->filter(fn (Server $server) => !$server->isSuspended())->values();

        DB::transaction(function () use ($user) {
            $user->forceFill([
                'suspended' => true,
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();
            $user->apiKeys()->delete();
            $this->forgetSessions($user);
        });

        // Suspend servers outside the account transaction so a Wings failure on one
        // server does not roll back the account suspension itself.
        foreach ($servers as $server) {
            try {
                $this->suspensionService->toggle($server, SuspensionService::ACTION_SUSPEND, false);
                $server->forceFill(['suspended_for_account' => true])->skipValidation()->save();
            } catch (\Throwable $exception) {
                Log::warning('Failed to suspend server during account suspension.', [
                    'user_id' => $user->id,
                    'server_id' => $server->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $this->revokeAccessSafely($user->refresh());
        $this->notifySafely($user, new AccountSuspended($user), 'account_suspended');
    }

    /**
     * Unsuspend a user account and restore servers suspended by the account action.
     *
     * @throws \Throwable
     */
    public function unsuspend(User $user): void
    {
        if (!$user->suspended) {
            return;
        }

        $servers = $user->servers()
            ->where('suspended_for_account', true)
            ->get();

        DB::transaction(function () use ($user) {
            $user->forceFill(['suspended' => false])->save();
        });

        foreach ($servers as $server) {
            try {
                $this->suspensionService->toggle($server, SuspensionService::ACTION_UNSUSPEND, false);
                $server->forceFill(['suspended_for_account' => false])->skipValidation()->save();
            } catch (\Throwable $exception) {
                Log::warning('Failed to unsuspend server during account unsuspension.', [
                    'user_id' => $user->id,
                    'server_id' => $server->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $this->notifySafely($user->refresh(), new AccountUnsuspended($user), 'account_unsuspended');
    }

    /**
     * Drop tracked browser sessions (and Laravel session store entries) so the
     * user is forced to log in again.
     */
    private function forgetSessions(User $user): void
    {
        try {
            $this->userSessionService->revokeAll($user);
        } catch (\Throwable $exception) {
            Log::warning('Failed to clear tracked sessions during account suspension.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        if (config('session.driver') !== 'database') {
            return;
        }

        try {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        } catch (\Throwable $exception) {
            Log::warning('Failed to clear sessions during account suspension.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Revoke SFTP/websocket access without failing the HTTP request if the queue/Wings path errors.
     */
    private function revokeAccessSafely(User $user): void
    {
        try {
            PasswordChanged::dispatch($user);
        } catch (\Throwable $exception) {
            Log::warning('Failed to revoke remote access during account suspension.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function notifySafely(User $user, object $notification, string $type): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $exception) {
            Log::warning('Failed to send account suspension notification.', [
                'user_id' => $user->id,
                'type' => $type,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
