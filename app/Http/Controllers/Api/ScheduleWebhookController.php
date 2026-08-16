<?php

namespace Pterodactyl\Http\Controllers\Api;

use Illuminate\Http\Request;
use Pterodactyl\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Schedules\ProcessScheduleService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ScheduleWebhookController extends Controller
{
    public function __construct(private ProcessScheduleService $service)
    {
    }

    /**
     * Execute a webhook-triggered schedule using the secret token in the URL.
     *
     * @throws \Throwable
     */
    public function __invoke(Request $request, string $webhookToken): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        /** @var Schedule|null $schedule */
        $schedule = Schedule::query()
            ->with(['tasks', 'server'])
            ->where('trigger', Schedule::TRIGGER_WEBHOOK)
            ->where('webhook_token', $webhookToken)
            ->first();

        if (is_null($schedule) || is_null($schedule->server)) {
            throw new NotFoundHttpException();
        }

        if (!$schedule->is_active) {
            throw new AccessDeniedHttpException('This webhook schedule is disabled.');
        }

        if (!is_null($schedule->server->status)) {
            throw new ConflictHttpException('The server is not in a runnable state.');
        }

        if ($schedule->is_processing) {
            throw new ConflictHttpException('This schedule is already processing.');
        }

        $this->service->handle($schedule, true);

        Activity::event('server:schedule.execute')
            ->anonymous()
            ->subject($schedule)
            ->property(['name' => $schedule->name, 'trigger' => Schedule::TRIGGER_WEBHOOK])
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_ACCEPTED);
    }
}
