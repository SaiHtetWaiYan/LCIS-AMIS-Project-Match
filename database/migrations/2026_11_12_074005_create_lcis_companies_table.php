<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Local copy of LCIS companies (GET /companies), keyed by the LCIS company id. Supplies the English name.
     */
    public function up(): void
    {
        Schema::create('lcis_companies', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->text('name')->nullable();
            $table->text('name_eng')->nullable();
            $table->jsonb('raw');
            $table->char('payload_hash', 64);
            $table->timestamp('synced_at');
            $table->timestamp('missing_since')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lcis_companies');
    }
};
