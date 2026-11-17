<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The side whose users see the client's token in the guide, and the token itself, encrypted with
     * APP_KEY. token_hash stays the lookup key for authenticating requests.
     */
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->string('side', 8)->nullable()->after('name');
            $table->text('token')->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropColumn(['side', 'token']);
        });
    }
};
