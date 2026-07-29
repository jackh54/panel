<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('suspended')->default(false)->after('root_admin');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('suspended_for_account')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('suspended');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('suspended_for_account');
        });
    }
};
