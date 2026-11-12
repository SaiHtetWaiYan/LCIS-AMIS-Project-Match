<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalised names and activity texts of both sides, one row per text, rebuilt after each
     * sync. The trigram index lets suggestions find similar texts without scanning every row.
     */
    public function up(): void
    {
        Schema::create('match_terms', function (Blueprint $table) {
            $table->id();
            $table->string('side', 8);
            $table->unsignedBigInteger('record_id');
            $table->string('kind', 16);
            $table->string('source', 32);
            $table->text('text');
            $table->text('text_norm');
            $table->index(['side', 'record_id']);
            $table->index(['side', 'kind']);
        });

        DB::statement('CREATE INDEX match_terms_text_norm_trgm ON match_terms USING gin (text_norm gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('match_terms');
    }
};
