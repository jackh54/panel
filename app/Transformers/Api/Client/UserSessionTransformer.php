<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\UserSession;

class UserSessionTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return UserSession::RESOURCE_NAME;
    }

    /**
     * Transform a tracked browser session into an API representation.
     */
    public function transform(UserSession $model): array
    {
        $isCurrent = isset($this->request)
            && $this->request->hasSession()
            && $model->session_id === $this->request->session()->getId();

        return [
            'uuid' => $model->uuid,
            'ip_address' => $model->ip_address,
            'user_agent' => $model->user_agent,
            'is_current' => $isCurrent,
            'last_used_at' => $model->last_used_at->toAtomString(),
            'created_at' => $model->created_at?->toAtomString(),
        ];
    }
}
