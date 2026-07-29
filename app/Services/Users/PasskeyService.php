<?php

namespace Pterodactyl\Services\Users;

use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use lbuchs\WebAuthn\WebAuthn;
use Pterodactyl\Models\UserPasskey;
use Illuminate\Support\Facades\Cache;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthnException;
use Pterodactyl\Exceptions\DisplayException;

class PasskeyService
{
    private const REGISTER_CACHE_PREFIX = 'passkey:register:';
    private const AUTH_CACHE_PREFIX = 'passkey:auth:';
    private const CHALLENGE_TTL_SECONDS = 300;

    /**
     * Build WebAuthn registration options for an authenticated user.
     *
     * @return array{token_id: string, publicKey: object}
     *
     * @throws DisplayException
     */
    public function createRegistrationOptions(User $user): array
    {
        $webauthn = $this->makeWebAuthn();
        $exclude = $user->passkeys->map(fn (UserPasskey $passkey) => base64_decode($passkey->credential_id, true) ?: '')->filter()->values()->all();

        $args = $webauthn->getCreateArgs(
            hex2bin(str_replace('-', '', $user->uuid)) ?: $user->uuid,
            $user->username,
            $user->name ?: $user->username,
            60 * 4,
            true,
            'preferred',
            null,
            $exclude
        );

        $tokenId = Str::random(64);
        Cache::put(self::REGISTER_CACHE_PREFIX . $tokenId, [
            'user_id' => $user->id,
            'challenge' => $webauthn->getChallenge()->getBinaryString(),
        ], self::CHALLENGE_TTL_SECONDS);

        return [
            'token_id' => $tokenId,
            'publicKey' => $args->publicKey,
        ];
    }

    /**
     * Verify a registration response and persist the new passkey.
     *
     * @param array{id: string, rawId: string, type: string, response: array{clientDataJSON: string, attestationObject: string}, transports?: string[]} $credential
     *
     * @throws DisplayException
     * @throws \Throwable
     */
    public function storeRegistration(User $user, string $tokenId, string $name, array $credential): UserPasskey
    {
        $cached = Cache::pull(self::REGISTER_CACHE_PREFIX . $tokenId);
        if (!is_array($cached) || ($cached['user_id'] ?? null) !== $user->id || empty($cached['challenge'])) {
            throw new DisplayException('Passkey registration expired. Please try again.');
        }

        $clientDataJSON = $this->decodeBinary($credential['response']['clientDataJSON'] ?? '');
        $attestationObject = $this->decodeBinary($credential['response']['attestationObject'] ?? '');

        try {
            $webauthn = $this->makeWebAuthn();
            $data = $webauthn->processCreate(
                $clientDataJSON,
                $attestationObject,
                $cached['challenge'],
                false,
                true,
                false
            );
        } catch (WebAuthnException $exception) {
            throw new DisplayException('Unable to register passkey: ' . $exception->getMessage());
        }

        $credentialId = base64_encode($data->credentialId);
        if (UserPasskey::query()->where('credential_id', $credentialId)->exists()) {
            throw new DisplayException('This passkey is already registered on an account.');
        }

        $passkey = $user->passkeys()->make()->forceFill([
            'uuid' => Str::uuid()->toString(),
            'name' => $name,
            'credential_id' => $credentialId,
            'public_key' => $data->credentialPublicKey,
            'aaguid' => $this->normalizeAaguid($data->AAGUID ?? null),
            'sign_count' => (int) ($data->signatureCounter ?? 0),
            'transports' => $credential['transports'] ?? null,
        ]);
        $passkey->saveOrFail();

        return $passkey->refresh();
    }

    /**
     * Build discoverable-credential authentication options for passwordless login.
     *
     * @return array{token_id: string, publicKey: object}
     */
    public function createAuthenticationOptions(): array
    {
        $webauthn = $this->makeWebAuthn();
        $args = $webauthn->getGetArgs([], 60 * 4, true, true, true, true, true, 'preferred');

        $tokenId = Str::random(64);
        Cache::put(self::AUTH_CACHE_PREFIX . $tokenId, [
            'challenge' => $webauthn->getChallenge()->getBinaryString(),
        ], self::CHALLENGE_TTL_SECONDS);

        return [
            'token_id' => $tokenId,
            'publicKey' => $args->publicKey,
        ];
    }

    /**
     * Verify an authentication assertion and return the owning user.
     *
     * @param array{id: string, rawId: string, type: string, response: array{clientDataJSON: string, authenticatorData: string, signature: string, userHandle?: string|null}} $credential
     *
     * @throws DisplayException
     */
    public function verifyAuthentication(string $tokenId, array $credential): User
    {
        $cached = Cache::pull(self::AUTH_CACHE_PREFIX . $tokenId);
        if (!is_array($cached) || empty($cached['challenge'])) {
            throw new DisplayException('Passkey sign-in expired. Please try again.');
        }

        $credentialId = base64_encode($this->decodeBinary($credential['rawId'] ?? $credential['id'] ?? ''));
        /** @var UserPasskey|null $passkey */
        $passkey = UserPasskey::query()->where('credential_id', $credentialId)->first();
        if (!$passkey) {
            throw new DisplayException('No passkey matched this authenticator.');
        }

        $clientDataJSON = $this->decodeBinary($credential['response']['clientDataJSON'] ?? '');
        $authenticatorData = $this->decodeBinary($credential['response']['authenticatorData'] ?? '');
        $signature = $this->decodeBinary($credential['response']['signature'] ?? '');

        try {
            $webauthn = $this->makeWebAuthn();
            $webauthn->processGet(
                $clientDataJSON,
                $authenticatorData,
                $signature,
                $passkey->public_key,
                $cached['challenge'],
                $passkey->sign_count > 0 ? $passkey->sign_count : null,
                false,
                true
            );
        } catch (WebAuthnException $exception) {
            throw new DisplayException('Passkey verification failed: ' . $exception->getMessage());
        }

        $newCounter = $webauthn->getSignatureCounter();
        $passkey->forceFill([
            'sign_count' => is_int($newCounter) ? $newCounter : $passkey->sign_count,
            'last_used_at' => now(),
        ])->save();

        $user = $passkey->user;
        if (!$user || $user->suspended) {
            throw new DisplayException(trans('auth.account_suspended'));
        }

        return $user;
    }

    private function makeWebAuthn(): WebAuthn
    {
        $rpId = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $rpName = (string) (config('app.name') ?: 'Pterodactyl');

        // Prefer standard base64url encoding so browser PublicKeyCredential options
        // do not need the library's proprietary =?BINARY?B?...?= wrapper.
        return new WebAuthn($rpName, $rpId, ['none', 'packed', 'apple', 'android-key', 'android-safetynet', 'fido-u2f', 'tpm'], true);
    }

    private function decodeBinary(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '=?BINARY?B?') && str_ends_with($value, '?=')) {
            $decoded = base64_decode(substr($value, 11, -2), true);

            return $decoded === false ? '' : $decoded;
        }

        $normalized = strtr($value, '-_', '+/');
        $pad = strlen($normalized) % 4;
        if ($pad > 0) {
            $normalized .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($normalized, true);

        return $decoded === false ? '' : $decoded;
    }

    private function normalizeAaguid(mixed $aaguid): ?string
    {
        if ($aaguid instanceof ByteBuffer) {
            $aaguid = $aaguid->getBinaryString();
        }

        if (!is_string($aaguid) || $aaguid === '' || $aaguid === str_repeat("\0", 16)) {
            return null;
        }

        if (strlen($aaguid) === 16) {
            $hex = bin2hex($aaguid);

            return sprintf(
                '%s-%s-%s-%s-%s',
                substr($hex, 0, 8),
                substr($hex, 8, 4),
                substr($hex, 12, 4),
                substr($hex, 16, 4),
                substr($hex, 20, 12)
            );
        }

        if (Str::isUuid($aaguid)) {
            return $aaguid;
        }

        return null;
    }
}
