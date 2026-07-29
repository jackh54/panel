<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserPasskey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Pterodactyl\Models\UserPasskey>
 */
class UserPasskeyFactory extends Factory
{
    protected $model = UserPasskey::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'credential_id' => base64_encode(random_bytes(32)),
            'public_key' => "-----BEGIN PUBLIC KEY-----\nMFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE\n-----END PUBLIC KEY-----",
            'aaguid' => Str::uuid()->toString(),
            'sign_count' => 0,
            'transports' => ['internal'],
            'last_used_at' => null,
        ];
    }
}
