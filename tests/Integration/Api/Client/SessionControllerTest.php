<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use Illuminate\Support\Str;
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
     * Revoking a device must destroy the Laravel session, invalidate remember-me,
     * and refuse to recreate the tracking row if that session id is reused.
     */
    public function testRevokedSessionCannotBeResurrected(): void
    {
        $user = User::factory()->create([
            'remember_token' => Str::random(60),
        ]);
        $originalRemember = $user->remember_token;

        $sessionId = str_repeat('a', 40);
        $session = UserSession::factory()->for($user)->create([
            'session_id' => $sessionId,
        ]);

        // Seed a Laravel session payload so destroy has something real to remove.
        $handler = $this->app->make('session')->getHandler();
        $handler->write($sessionId, serialize(['login_web' => $user->id]));

        $this->asFrontend($user)
            ->deleteJson('/api/client/account/sessions/' . $session->uuid)
            ->assertNoContent();

        $this->assertDatabaseMissing('user_sessions', ['id' => $session->id]);
        $this->assertSame('', $handler->read($sessionId));
        $this->assertNotSame($originalRemember, $user->refresh()->remember_token);
        $this->assertTrue($this->app->make(UserSessionService::class)->isRevoked($sessionId));

        // Simulate the revoked browser coming back with the same session id.
        $this->withSession([])->flushSession();
        $store = $this->app->make('session.store');
        $store->setId($sessionId);
        $this->assertSame($sessionId, $store->getId());
        $store->start();
        $store->put('login_web_' . sha1('Illuminate\Auth\SessionGuard'), $user->id);

        $request = \Illuminate\Http\Request::create('/api/client/account/sessions', 'GET');
        $request->setLaravelSession($store);
        $request->setUserResolver(fn () => $user);

        $this->assertFalse(
            $this->app->make(UserSessionService::class)->touchFromRequest($request),
            'touchFromRequest must reject a previously revoked session id.'
        );
        $this->assertDatabaseMissing('user_sessions', ['session_id' => $sessionId]);
    }

    public function testRevokeOthersInvalidatesRememberTokenOnce(): void
    {
        $user = User::factory()->create([
            'remember_token' => 'original-remember-token-value-here-xx',
        ]);
        UserSession::factory()->for($user)->create(['session_id' => 'keep-me']);
        UserSession::factory()->for($user)->create();
        UserSession::factory()->for($user)->create();

        $this->app->make(UserSessionService::class)->revokeOthers($user, 'keep-me');

        $this->assertNotSame('original-remember-token-value-here-xx', $user->refresh()->remember_token);
        $this->assertDatabaseHas('user_sessions', ['session_id' => 'keep-me']);
        $this->assertSame(1, $user->sessions()->count());
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
