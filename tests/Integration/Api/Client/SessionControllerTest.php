<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSession;
use Pterodactyl\Http\Middleware\VerifyCsrfToken;
use Pterodactyl\Services\Users\UserSessionService;

class SessionControllerTest extends ClientApiIntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // These endpoints are first-party SPA calls; CSRF is verified in production via
        // Sanctum's stateful stack. Disable it here so session-bound assertions stay focused.
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->disableCookieEncryption();
        $this->withSession([]);
    }

    protected function tearDown(): void
    {
        UserSession::query()->delete();

        parent::tearDown();
    }

    /**
     * Ensure a user only sees their own sessions.
     */
    public function testSessionsAreReturnedForAuthenticatedUser(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $owned = UserSession::factory()->for($user)->create([
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
        ]);
        $foreign = UserSession::factory()->for($other)->create();

        $response = $this->asFrontend($user)
            ->getJson('/api/client/account/sessions')
            ->assertOk()
            ->assertJsonPath('object', 'list');

        $uuids = collect($response->json('data'))->pluck('attributes.uuid');

        $this->assertTrue($uuids->contains($owned->uuid));
        $this->assertFalse($uuids->contains($foreign->uuid));
        $this->assertTrue(
            UserSession::query()->where('user_id', $user->id)->count() >= 2,
            'Listing sessions should also track the current browser session.'
        );
    }

    /**
     * Ensure a user can revoke another device session, but not another user's.
     */
    public function testSessionCanBeRevoked(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $session = UserSession::factory()->for($user)->create();
        $foreign = UserSession::factory()->for($otherUser)->create();

        $this->asFrontend($user)
            ->deleteJson('/api/client/account/sessions/' . $session->uuid)
            ->assertNoContent();

        $this->assertDatabaseMissing('user_sessions', ['id' => $session->id]);
        $this->assertDatabaseHas('user_sessions', ['id' => $foreign->id]);

        $this->asFrontend($user)
            ->deleteJson('/api/client/account/sessions/' . $foreign->uuid)
            ->assertNotFound();
    }

    public function testCurrentSessionCannotBeRevoked(): void
    {
        $user = User::factory()->create();

        $store = $this->app->make('session.store');
        $store->setId('current-device-session');
        $store->start();

        $session = UserSession::factory()->for($user)->create([
            'session_id' => $store->getId(),
        ]);

        $base = \Illuminate\Http\Request::create(
            '/api/client/account/sessions/' . $session->uuid,
            'DELETE'
        );
        $base->setLaravelSession($store);
        $base->setUserResolver(fn () => $user);

        $request = \Pterodactyl\Http\Requests\Api\Client\ClientApiRequest::createFrom($base);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($store);

        $this->assertTrue($request->hasSession());
        $this->assertSame($store->getId(), $request->session()->getId());

        try {
            $this->app->make(\Pterodactyl\Http\Controllers\Api\Client\SessionController::class)
                ->delete($request, $session->uuid);
            $this->fail('Expected DisplayException was not thrown.');
        } catch (\Pterodactyl\Exceptions\DisplayException $exception) {
            $this->assertSame(
                'You cannot revoke this device while you are using it. Sign out instead.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('user_sessions', ['id' => $session->id]);
    }

    public function testOtherSessionsCanBeRevokedInBulk(): void
    {
        $user = User::factory()->create();
        $current = UserSession::factory()->for($user)->create([
            'session_id' => 'keep-me',
        ]);
        $other = UserSession::factory()->for($user)->create();
        $other2 = UserSession::factory()->for($user)->create();

        $count = $this->app->make(UserSessionService::class)->revokeOthers($user, 'keep-me');

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('user_sessions', ['id' => $current->id, 'session_id' => 'keep-me']);
        $this->assertDatabaseMissing('user_sessions', ['id' => $other->id]);
        $this->assertDatabaseMissing('user_sessions', ['id' => $other2->id]);
    }

    public function testDeleteOthersEndpointRevokesNonCurrentSessions(): void
    {
        $user = User::factory()->create();
        $other = UserSession::factory()->for($user)->create();

        $this->asFrontend($user)
            ->deleteJson('/api/client/account/sessions/other')
            ->assertNoContent();

        $this->assertDatabaseMissing('user_sessions', ['id' => $other->id]);
    }

    /**
     * Authenticate as a first-party SPA request so Sanctum starts a session.
     */
    private function asFrontend(User $user): self
    {
        return $this
            ->withHeader('Origin', 'http://localhost')
            ->withHeader('Referer', 'http://localhost')
            ->withUnencryptedCookie(config('session.cookie'), session()->getId())
            ->actingAs($user);
    }
}
