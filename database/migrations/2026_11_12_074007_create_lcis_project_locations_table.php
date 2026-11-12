<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Land locations of an LCIS project, rebuilt on every sync. Falls back to the responsible province/district until LCIS sends locations[].
     */
    public function up(): void
    {
        Schema::create('lcis_project_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lcis_project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('province_id')->nullable();
            $table->string('province_name')->nullable();
            $table->unsignedInteger('district_id')->nullable();
            $table->string('district_name')->nullable();
            $table->unsignedBigInteger('village_id')->nullable();
            $table->string('village_name')->nullable();
            $table->boolean('is_fallback')->default(false);
            $table->index(['province_id', 'district_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lcis_project_locations');
    }
};
