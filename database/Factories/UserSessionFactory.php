<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Pterodactyl\Models\UserSession>
 */
class UserSessionFactory extends Factory
{
    protected $model = UserSession::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'session_id' => Str::random(40),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'last_used_at' => now(),
        ];
    }
}
