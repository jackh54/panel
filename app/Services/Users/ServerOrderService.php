<?php

namespace Pterodactyl\Services\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\UserServerOrder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ServerOrderService
{
    /**
     * Persist a custom server order for the given user.
     *
     * @param array<int, string> $uuids Ordered list of server UUIDs
     */
    public function setOrder(User $user, array $uuids, int $offset = 0): void
    {
        $uuids = array_values(array_unique($uuids));
        if (empty($uuids)) {
            throw new BadRequestHttpException('At least one server UUID is required.');
        }

        $accessible = $user->accessibleServers()
            ->whereIn('servers.uuid', $uuids)
            ->get(['servers.id', 'servers.uuid'])
            ->keyBy('uuid');

        if ($accessible->count() !== count($uuids)) {
            throw new BadRequestHttpException('One or more servers could not be found or you do not have access to them.');
        }

        DB::transaction(function () use ($user, $uuids, $accessible, $offset) {
            foreach ($uuids as $index => $uuid) {
                /** @var Server $server */
                $server = $accessible->get($uuid);

                UserServerOrder::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'server_id' => $server->id,
                    ],
                    [
                        'sort_order' => $offset + $index,
                    ]
                );
            }
        });
    }
}
