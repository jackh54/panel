<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('trigger', 16)->default('cron')->after('name');
            $table->char('webhook_token', 64)->nullable()->unique()->after('trigger');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropUnique(['webhook_token']);
            $table->dropColumn(['trigger', 'webhook_token']);
        });
    }
};
