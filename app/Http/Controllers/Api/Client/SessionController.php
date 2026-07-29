<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\UserSession;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Users\UserSessionService;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Transformers\Api\Client\UserSessionTransformer;

class SessionController extends ClientApiController
{
    public function __construct(private UserSessionService $sessionService)
    {
        parent::__construct();
    }

    /**
     * List active browser sessions for the authenticated account.
     */
    public function index(ClientApiRequest $request): array
    {
        // Ensure the current browser is present in the list even if this is the
        // first request after upgrading to session tracking.
        $this->sessionService->touchFromRequest($request);

        $sessions = $this->sessionService->getActiveForUser($request->user());

        return $this->fractal->collection($sessions)
            ->transformWith($this->getTransformer(UserSessionTransformer::class))
            ->toArray();
    }

    /**
     * Revoke a single browser session belonging to the authenticated user.
     *
     * @throws DisplayException
     */
    public function delete(ClientApiRequest $request, string $uuid): JsonResponse
    {
        /** @var UserSession $session */
        $session = $request->user()->sessions()->where('uuid', $uuid)->firstOrFail();

        if ($request->hasSession() && $session->session_id === $request->session()->getId()) {
            throw new DisplayException('You cannot revoke this device while you are using it. Sign out instead.');
        }

        Activity::event('user:session.revoke')
            ->property([
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
            ])
            ->log();

        $this->sessionService->revoke($request->user(), $session);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Sign out every other browser session for the authenticated user.
     */
    public function deleteOthers(ClientApiRequest $request): JsonResponse
    {
        if (!$request->hasSession()) {
            throw new DisplayException('Unable to determine your current session.');
        }

        $count = $this->sessionService->revokeOthers(
            $request->user(),
            $request->session()->getId()
        );

        if ($count > 0) {
            Activity::event('user:session.revoke-others')
                ->property('count', $count)
                ->log();
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
