<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contract ↔ company links from the AMIS companies endpoint, the only source for this link.
     */
    public function up(): void
    {
        Schema::create('amis_company_contract', function (Blueprint $table) {
            $table->foreignId('amis_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amis_contract_id')->constrained()->cascadeOnDelete();
            $table->decimal('shareholding_pct', 5, 2)->nullable();
            $table->primary(['amis_company_id', 'amis_contract_id']);
            $table->index('amis_contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amis_company_contract');
    }
};
