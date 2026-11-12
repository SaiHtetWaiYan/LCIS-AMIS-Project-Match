<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Local copy of AMIS companies linked to rent-land contracts (GET /companies), keyed by the AMIS company id.
     */
    public function up(): void
    {
        Schema::create('amis_companies', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->text('name_lao')->nullable();
            $table->text('name_eng')->nullable();
            $table->string('company_type')->nullable();
            $table->string('tax_id')->nullable()->index();
            $table->unsignedInteger('province_id')->nullable();
            $table->unsignedInteger('district_id')->nullable();
            $table->string('district_name')->nullable();
            $table->string('village')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('raw');
            $table->char('payload_hash', 64);
            $table->timestamp('synced_at');
            $table->timestamp('missing_since')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amis_companies');
    }
};
