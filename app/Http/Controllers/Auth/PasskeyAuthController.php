<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Users\PasskeyService;

class PasskeyAuthController extends AbstractLoginController
{
    public function __construct(private PasskeyService $passkeyService)
    {
        parent::__construct();
    }

    /**
     * Return discoverable-credential options for passwordless passkey login.
     */
    public function options(): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->passkeyService->createAuthenticationOptions(),
        ]);
    }

    /**
     * Verify a WebAuthn assertion and establish an authenticated session.
     *
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function verify(Request $request): JsonResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->sendLockoutResponse($request);
        }

        $this->validate($request, [
            'token_id' => ['required', 'string'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string'],
            'credential.response' => ['required', 'array'],
            'credential.response.clientDataJSON' => ['required', 'string'],
            'credential.response.authenticatorData' => ['required', 'string'],
            'credential.response.signature' => ['required', 'string'],
        ]);

        try {
            $user = $this->passkeyService->verifyAuthentication(
                $request->input('token_id'),
                $request->input('credential')
            );
        } catch (DisplayException $exception) {
            $this->sendFailedLoginResponse($request, null, $exception->getMessage());

            // sendFailedLoginResponse never returns; keep static analysis happy.
            throw $exception;
        }

        Activity::event('auth:passkey')->subject($user)->withRequestMetadata()->log();

        return $this->sendLoginResponse($user, $request);
    }
}
