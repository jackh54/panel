<?php

namespace Pterodactyl\Http\Requests\Api\Client;

class UpdateServerOrderRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'servers' => 'required|array|min:1',
            'servers.*' => 'required|string|uuid',
            'offset' => 'sometimes|integer|min:0',
        ];
    }
}
