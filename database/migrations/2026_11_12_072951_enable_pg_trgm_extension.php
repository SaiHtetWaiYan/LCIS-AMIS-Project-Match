<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Enable trigram matching, used to score company and activity name similarity.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
    }

    /**
     * The extension is left installed: other database objects may depend on it.
     */
    public function down(): void
    {
        //
    }
};
