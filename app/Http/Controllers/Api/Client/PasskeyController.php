<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\UserPasskey;
use Pterodactyl\Services\Users\PasskeyService;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Transformers\Api\Client\UserPasskeyTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\StorePasskeyRequest;

class PasskeyController extends ClientApiController
{
    public function __construct(private PasskeyService $passkeyService)
    {
        parent::__construct();
    }

    /**
     * List passkeys registered on the authenticated account.
     */
    public function index(ClientApiRequest $request): array
    {
        return $this->fractal->collection($request->user()->passkeys()->orderByDesc('created_at')->get())
            ->transformWith($this->getTransformer(UserPasskeyTransformer::class))
            ->toArray();
    }

    /**
     * Return WebAuthn PublicKeyCredentialCreationOptions for a new passkey.
     */
    public function options(ClientApiRequest $request): JsonResponse
    {
        $options = $this->passkeyService->createRegistrationOptions($request->user());

        return new JsonResponse([
            'data' => $options,
        ]);
    }

    /**
     * Persist a newly registered passkey after client attestation.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Throwable
     */
    public function store(StorePasskeyRequest $request): array
    {
        $passkey = $this->passkeyService->storeRegistration(
            $request->user(),
            $request->input('token_id'),
            $request->input('name'),
            $request->input('credential')
        );

        Activity::event('user:passkey.create')
            ->subject($passkey)
            ->property('name', $passkey->name)
            ->log();

        return $this->fractal->item($passkey)
            ->transformWith($this->getTransformer(UserPasskeyTransformer::class))
            ->toArray();
    }

    /**
     * Remove a passkey from the authenticated account.
     */
    public function delete(ClientApiRequest $request, string $uuid): JsonResponse
    {
        /** @var UserPasskey $passkey */
        $passkey = $request->user()->passkeys()->where('uuid', $uuid)->firstOrFail();

        $passkey->delete();

        Activity::event('user:passkey.delete')
            ->subject($passkey)
            ->property('name', $passkey->name)
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
