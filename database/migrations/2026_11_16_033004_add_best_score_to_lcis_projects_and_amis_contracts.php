<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each record's best score against the other system, so lists can sort by it: the score of
     * its proposed or matched pair, otherwise of its top suggestion. Recalculated after each
     * sync and when a pair changes (App\Services\Matching\BestScoreRecorder).
     */
    public function up(): void
    {
        foreach (['lcis_projects', 'amis_contracts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('best_score', 5, 4)->nullable()->index();
                $table->unsignedBigInteger('best_counterpart_id')->nullable()->index();
                $table->boolean('best_is_pair')->default(false);
                $table->timestamp('best_scored_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['lcis_projects', 'amis_contracts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['best_score', 'best_counterpart_id', 'best_is_pair', 'best_scored_at']);
            });
        }
    }
};
