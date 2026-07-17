<?php

namespace Pterodactyl\Services\Servers;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Notifications\ServerSuspended;
use Pterodactyl\Notifications\ServerUnsuspended;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SuspensionService
{
    public const ACTION_SUSPEND = 'suspend';
    public const ACTION_UNSUSPEND = 'unsuspend';

    public function __construct(
        private DaemonServerRepository $daemonServerRepository,
    ) {
    }

    /**
     * Suspends or unsuspends a server on the system.
     *
     * @throws \Throwable
     */
    public function toggle(Server $server, string $action = self::ACTION_SUSPEND, bool $notify = true): void
    {
        Assert::oneOf($action, [self::ACTION_SUSPEND, self::ACTION_UNSUSPEND]);

        $isSuspending = $action === self::ACTION_SUSPEND;
        if ($isSuspending === $server->isSuspended()) {
            return;
        }

        if (!is_null($server->transfer)) {
            throw new ConflictHttpException('Cannot toggle suspension status on a server that is currently being transferred.');
        }

        $server->update([
            'status' => $isSuspending ? Server::STATUS_SUSPENDED : null,
        ]);

        try {
            $this->daemonServerRepository->setServer($server)->sync();
        } catch (\Exception $exception) {
            $server->update([
                'status' => $isSuspending ? null : Server::STATUS_SUSPENDED,
            ]);
            throw $exception;
        }

        if (!$notify) {
            return;
        }

        $server = $server->refresh()->loadMissing('user');
        $owner = $server->user;

        try {
            if ($isSuspending) {
                $owner->notify(new ServerSuspended($server));
            } else {
                $owner->notify(new ServerUnsuspended($server));
            }
        } catch (\Throwable $exception) {
            Log::warning('Failed to send server suspension notification.', [
                'server_id' => $server->id,
                'action' => $action,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
