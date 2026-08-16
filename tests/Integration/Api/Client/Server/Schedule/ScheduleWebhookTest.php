<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Schedule;

use Pterodactyl\Models\Task;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Schedule;
use Illuminate\Support\Facades\Bus;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class ScheduleWebhookTest extends ClientApiIntegrationTestCase
{
    public function testWebhookExecutesScheduleTasks()
    {
        Bus::fake();

        $server = $this->createServerModel();
        /** @var Schedule $schedule */
        $schedule = Schedule::factory()->webhook()->create([
            'server_id' => $server->id,
            'is_active' => true,
        ]);
        /** @var Task $task */
        $task = Task::factory()->create([
            'schedule_id' => $schedule->id,
            'sequence_id' => 1,
            'time_offset' => 2,
        ]);

        $this->postJson('/hooks/schedule/' . $schedule->webhook_token)
            ->assertStatus(Response::HTTP_ACCEPTED);

        Bus::assertDispatched(function (RunTaskJob $job) use ($task) {
            $this->assertNull($job->delay);
            $this->assertSame($task->id, $job->task->id);

            return true;
        });
    }

    public function testWebhookCanBeTriggeredWithGet()
    {
        Bus::fake();

        $server = $this->createServerModel();
        /** @var Schedule $schedule */
        $schedule = Schedule::factory()->webhook()->create(['server_id' => $server->id]);
        Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1]);

        $this->getJson('/hooks/schedule/' . $schedule->webhook_token)
            ->assertStatus(Response::HTTP_ACCEPTED);
    }

    public function testUnknownWebhookTokenReturnsNotFound()
    {
        $this->postJson('/hooks/schedule/' . str_repeat('a', 64))->assertNotFound();
    }

    public function testDisabledWebhookIsRejected()
    {
        $server = $this->createServerModel();
        /** @var Schedule $schedule */
        $schedule = Schedule::factory()->webhook()->create([
            'server_id' => $server->id,
            'is_active' => false,
        ]);
        Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1]);

        $this->postJson('/hooks/schedule/' . $schedule->webhook_token)->assertForbidden();
    }

    public function testWebhookForSuspendedServerIsRejected()
    {
        $server = $this->createServerModel(['status' => Server::STATUS_SUSPENDED]);
        /** @var Schedule $schedule */
        $schedule = Schedule::factory()->webhook()->create(['server_id' => $server->id]);
        Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1]);

        $this->postJson('/hooks/schedule/' . $schedule->webhook_token)->assertStatus(Response::HTTP_CONFLICT);
    }

    public function testWebhookWithoutTasksReturnsError()
    {
        $server = $this->createServerModel();
        /** @var Schedule $schedule */
        $schedule = Schedule::factory()->webhook()->create(['server_id' => $server->id]);

        $this->postJson('/hooks/schedule/' . $schedule->webhook_token)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonPath('errors.0.detail', 'Cannot process schedule for task execution: no tasks are registered.');
    }

    public function testWebhookTokenCanBeRotated()
    {
        [$user, $server] = $this->generateTestAccount();

        /** @var Schedule $schedule */
        $schedule = Schedule::factory()->webhook()->create(['server_id' => $server->id]);
        $original = $schedule->webhook_token;

        $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}", [
            'name' => $schedule->name,
            'is_active' => true,
            'trigger' => 'webhook',
            'rotate_webhook_token' => true,
        ]);

        $response->assertOk();
        $schedule->refresh();

        $this->assertNotSame($original, $schedule->webhook_token);
        $this->assertStringContainsString($schedule->webhook_token, $response->json('attributes.webhook_url'));
        $this->assertStringNotContainsString($original, $response->json('attributes.webhook_url'));
    }
}
