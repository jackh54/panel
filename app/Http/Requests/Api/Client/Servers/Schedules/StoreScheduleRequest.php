<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Permission;

class StoreScheduleRequest extends ViewScheduleRequest
{
    public function permission(): string
    {
        return Permission::ACTION_SCHEDULE_CREATE;
    }

    public function rules(): array
    {
        $rules = Schedule::getRules();

        return [
            'name' => $rules['name'],
            'trigger' => 'sometimes|string|in:cron,webhook',
            'rotate_webhook_token' => 'sometimes|boolean',
            'is_active' => array_merge(['filled'], $rules['is_active']),
            'minute' => ['required_unless:trigger,webhook', 'string'],
            'hour' => ['required_unless:trigger,webhook', 'string'],
            'day_of_month' => ['required_unless:trigger,webhook', 'string'],
            'day_of_week' => ['required_unless:trigger,webhook', 'string'],
        ];
    }
}
