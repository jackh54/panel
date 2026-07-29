<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\UserPasskey;

class UserPasskeyTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return UserPasskey::RESOURCE_NAME;
    }

    public function transform(UserPasskey $model): array
    {
        return [
            'uuid' => $model->uuid,
            'name' => $model->name,
            'aaguid' => $model->aaguid,
            'transports' => $model->transports,
            'last_used_at' => $model->last_used_at?->toAtomString(),
            'created_at' => $model->created_at->toAtomString(),
        ];
    }
}
