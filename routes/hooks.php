<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\ScheduleWebhookController;

Route::match(['GET', 'POST'], '/schedule/{webhookToken}', ScheduleWebhookController::class)
    ->where('webhookToken', '[a-f0-9]{64}')
    ->name('hooks.schedule');
