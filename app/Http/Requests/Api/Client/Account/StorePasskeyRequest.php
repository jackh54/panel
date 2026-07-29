<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StorePasskeyRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:191'],
            'token_id' => ['required', 'string'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string'],
            'credential.response' => ['required', 'array'],
            'credential.response.clientDataJSON' => ['required', 'string'],
            'credential.response.attestationObject' => ['required', 'string'],
            'credential.transports' => ['nullable', 'array'],
            'credential.transports.*' => ['string'],
        ];
    }
}
