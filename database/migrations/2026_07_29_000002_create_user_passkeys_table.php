<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('user_passkeys', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36);
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->string('credential_id', 512);
            $table->text('public_key');
            $table->char('aaguid', 36)->nullable();
            $table->unsignedInteger('sign_count')->default(0);
            $table->json('transports')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('uuid');
            $table->unique('credential_id', 'user_passkeys_credential_id_unique');
            $table->index(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_passkeys');
    }
};
