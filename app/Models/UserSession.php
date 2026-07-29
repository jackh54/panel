<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $session_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property User $user
 *
 * @method static \Database\Factories\UserSessionFactory factory(...$parameters)
 */
class UserSession extends Model
{
    /** @use HasFactory<\Database\Factories\UserSessionFactory> */
    use HasFactory;

    public const RESOURCE_NAME = 'user_session';

    protected $table = 'user_sessions';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'last_used_at' => 'datetime',
    ];

    public static array $validationRules = [
        'uuid' => 'required|string|size:36',
        'user_id' => 'required|integer|exists:users,id',
        'session_id' => 'required|string',
        'ip_address' => 'nullable|string|max:45',
        'user_agent' => 'nullable|string',
        'last_used_at' => 'required|date',
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
