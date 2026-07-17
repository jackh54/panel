<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Response;
use Pterodactyl\Services\Users\ServerOrderService;
use Pterodactyl\Http\Requests\Api\Client\UpdateServerOrderRequest;

class ServerOrderController extends ClientApiController
{
    public function __construct(private ServerOrderService $serverOrderService)
    {
        parent::__construct();
    }

    /**
     * Persist the authenticated user's preferred server list order.
     */
    public function __invoke(UpdateServerOrderRequest $request): Response
    {
        $this->serverOrderService->setOrder(
            $request->user(),
            $request->input('servers', []),
            (int) $request->input('offset', 0)
        );

        return $this->returnNoContent();
    }
}
