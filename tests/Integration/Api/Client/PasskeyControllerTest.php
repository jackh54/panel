<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use Pterodactyl\Models\User;
use Pterodactyl\Models\UserPasskey;
use Illuminate\Support\Facades\Cache;
use Pterodactyl\Services\Users\PasskeyService;

class PasskeyControllerTest extends ClientApiIntegrationTestCase
{
    protected function tearDown(): void
    {
        UserPasskey::query()->delete();

        parent::tearDown();
    }

    public function testPasskeysAreReturnedForAuthenticatedUser(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $owned = UserPasskey::factory()->for($user)->create(['name' => 'Laptop']);
        UserPasskey::factory()->for($other)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/client/account/passkeys')
            ->assertOk()
            ->assertJsonPath('object', 'list');

        $uuids = collect($response->json('data'))->pluck('attributes.uuid');

        $this->assertTrue($uuids->contains($owned->uuid));
        $this->assertCount(1, $uuids);
        $this->assertJsonTransformedWith($response->json('data.0.attributes'), $owned);
    }

    public function testRegistrationOptionsAreReturned(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/client/account/passkeys/options')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token_id',
                    'publicKey' => [
                        'rp',
                        'user',
                        'challenge',
                        'pubKeyCredParams',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token_id'));
        $this->assertTrue(Cache::has('passkey:register:' . $response->json('data.token_id')));
    }

    public function testPasskeyCanBeDeleted(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $owned = UserPasskey::factory()->for($user)->create();
        $foreign = UserPasskey::factory()->for($other)->create();

        $this->actingAs($user)
            ->deleteJson('/api/client/account/passkeys/' . $owned->uuid)
            ->assertNoContent();

        $this->assertDatabaseMissing('user_passkeys', ['id' => $owned->id]);
        $this->assertDatabaseHas('user_passkeys', ['id' => $foreign->id]);

        $this->actingAs($user)
            ->deleteJson('/api/client/account/passkeys/' . $foreign->uuid)
            ->assertNotFound();
    }

    public function testStoreRejectsExpiredRegistrationToken(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/client/account/passkeys', [
                'name' => 'Phone',
                'token_id' => 'missing-token',
                'credential' => [
                    'id' => 'abc',
                    'rawId' => 'abc',
                    'type' => 'public-key',
                    'response' => [
                        'clientDataJSON' => base64_encode('{}'),
                        'attestationObject' => base64_encode('{}'),
                    ],
                ],
            ])
            ->assertStatus(400);
    }

    public function testAuthenticationOptionsArePublic(): void
    {
        $this->getJson('/auth/passkey/options')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token_id',
                    'publicKey' => [
                        'challenge',
                        'rpId',
                        'timeout',
                    ],
                ],
            ]);
    }

    public function testAuthenticationRejectsUnknownCredential(): void
    {
        $options = $this->app->make(PasskeyService::class)->createAuthenticationOptions();

        $this->postJson('/auth/passkey/verify', [
            'token_id' => $options['token_id'],
            'credential' => [
                'id' => base64_encode('unknown'),
                'rawId' => base64_encode('unknown'),
                'type' => 'public-key',
                'response' => [
                    'clientDataJSON' => base64_encode('{"type":"webauthn.get","challenge":"x","origin":"http://localhost"}'),
                    'authenticatorData' => base64_encode(str_repeat('a', 37)),
                    'signature' => base64_encode(str_repeat('b', 32)),
                ],
            ],
        ])->assertStatus(400);
    }
}
