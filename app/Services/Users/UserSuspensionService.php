<?php

namespace Pterodactyl\Services\Users;

use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Events\User\PasswordChanged;
use Pterodactyl\Notifications\AccountSuspended;
use Pterodactyl\Notifications\AccountUnsuspended;
use Pterodactyl\Services\Servers\SuspensionService;

class UserSuspensionService
{
    public function __construct(private SuspensionService $suspensionService)
    {
    }

    /**
     * Suspend a user account, cascade-suspend owned servers, revoke sessions/tokens.
     *
     * @throws \Throwable
     */
    public function suspend(User $user): void
    {
        if ($user->suspended) {
            return;
        }

        DB::transaction(function () use ($user) {
            $user->forceFill([
                'suspended' => true,
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();
            $user->apiKeys()->delete();

            $user->servers()->get()->each(function (Server $server) {
                if ($server->isSuspended()) {
                    return;
                }

                $this->suspensionService->toggle($server, SuspensionService::ACTION_SUSPEND);
                $server->forceFill(['suspended_for_account' => true])->save();
            });
        });

        PasswordChanged::dispatch($user->refresh());
        $user->notify(new AccountSuspended($user));
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

        DB::transaction(function () use ($user) {
            $user->forceFill(['suspended' => false])->save();

            $user->servers()
                ->where('suspended_for_account', true)
                ->get()
                ->each(function (Server $server) {
                    $this->suspensionService->toggle($server, SuspensionService::ACTION_UNSUSPEND);
                    $server->forceFill(['suspended_for_account' => false])->save();
                });
        });

        $user->notify(new AccountUnsuspended($user->refresh()));
    }
}
