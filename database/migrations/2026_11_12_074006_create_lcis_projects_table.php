<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Local copy of LCIS investment projects (GET /projects), keyed by the LCIS project id. Never hard-deleted.
     */
    public function up(): void
    {
        Schema::create('lcis_projects', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('project_code')->nullable()->index();
            $table->text('activity_name')->nullable();
            $table->unsignedInteger('project_type_id')->nullable()->index();
            $table->string('project_type')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->text('company_name')->nullable();
            $table->unsignedInteger('subsector_id')->nullable();
            $table->string('subsector_name')->nullable();
            $table->unsignedInteger('negotiation_status_id')->nullable();
            $table->string('negotiation_status_name')->nullable();
            $table->string('responsible_level')->nullable();
            $table->unsignedInteger('responsible_province_id')->nullable()->index();
            $table->string('responsible_province_name')->nullable();
            $table->unsignedInteger('responsible_district_id')->nullable();
            $table->string('responsible_district_name')->nullable();
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
        Schema::dropIfExists('lcis_projects');
    }
};
