<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $name
 * @property string $credential_id
 * @property string $public_key
 * @property string|null $aaguid
 * @property int $sign_count
 * @property array|null $transports
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property User $user
 *
 * @method static \Database\Factories\UserPasskeyFactory factory(...$parameters)
 */
class UserPasskey extends Model
{
    /** @use HasFactory<\Database\Factories\UserPasskeyFactory> */
    use HasFactory;

    public const RESOURCE_NAME = 'passkey';

    protected $table = 'user_passkeys';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'sign_count' => 'integer',
        'transports' => 'array',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = ['public_key', 'credential_id'];

    public static array $validationRules = [
        'uuid' => 'required|string|size:36',
        'user_id' => 'required|integer|exists:users,id',
        'name' => 'required|string|max:191',
        'credential_id' => 'required|string',
        'public_key' => 'required|string',
        'aaguid' => 'nullable|string|size:36',
        'sign_count' => 'required|integer|min:0',
        'transports' => 'nullable|array',
        'last_used_at' => 'nullable|date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Pterodactyl\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
