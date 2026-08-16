<?php

namespace Database\Factories;

use Pterodactyl\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Schedule::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName(),
        ];
    }

    public function webhook(): self
    {
        return $this->state(fn () => [
            'trigger' => Schedule::TRIGGER_WEBHOOK,
            'webhook_token' => Schedule::generateWebhookToken(),
            'next_run_at' => null,
        ]);
    }
}
