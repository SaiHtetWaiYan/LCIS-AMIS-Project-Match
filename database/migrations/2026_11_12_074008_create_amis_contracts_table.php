<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Local copy of AMIS rent-land contracts (GET /income/rent-land), keyed by contact_id. Column names fix the AMIS misspellings.
     */
    public function up(): void
    {
        Schema::create('amis_contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('contract_type')->nullable()->index();
            $table->unsignedInteger('business_type_id')->nullable();
            $table->string('business_type_name')->nullable();
            $table->string('contract_number')->nullable()->index();
            $table->text('contract_name')->nullable();
            $table->jsonb('partner_names');
            $table->string('status')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('from_province_id')->nullable()->index();
            $table->decimal('area_ha', 16, 4)->nullable();
            $table->char('key_fields_hash', 64);
            $table->timestamp('source_updated_at')->nullable();
            $table->jsonb('raw');
            $table->char('payload_hash', 64);
            $table->timestamp('synced_at');
            $table->timestamp('missing_since')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amis_contracts');
    }
};
