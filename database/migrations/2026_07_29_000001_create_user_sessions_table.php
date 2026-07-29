<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36);
            $table->unsignedInteger('user_id');
            $table->string('session_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_used_at');
            $table->timestamps();

            $table->unique('uuid');
            $table->unique('session_id');
            $table->index(['user_id', 'last_used_at']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
