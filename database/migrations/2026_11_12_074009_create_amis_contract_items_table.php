<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Land plots (items[]) of an AMIS contract, keyed by the AMIS item id and rebuilt on every sync.
     */
    public function up(): void
    {
        Schema::create('amis_contract_items', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->foreignId('amis_contract_id')->constrained()->cascadeOnDelete();
            $table->text('land_use_detail')->nullable();
            $table->unsignedInteger('province_id')->nullable();
            $table->unsignedInteger('district_id')->nullable();
            $table->string('district_name')->nullable();
            $table->string('village')->nullable();
            $table->decimal('area', 18, 4)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('area_ha', 16, 4)->nullable();
            $table->index(['province_id', 'district_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amis_contract_items');
    }
};
