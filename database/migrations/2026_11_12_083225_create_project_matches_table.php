<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A pairing of one LCIS project with one AMIS contract and its confirmation state.
     *
     * One-to-one is enforced here: a record may be in at most one proposed or matched pair. To allow
     * one LCIS project to match several AMIS contracts later, drop project_matches_active_lcis_unique
     * and change App\Services\Matches\MatchCardinality.
     */
    public function up(): void
    {
        Schema::create('project_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lcis_project_id')->constrained();
            $table->foreignId('amis_contract_id')->constrained();
            $table->string('status', 16)->index();
            $table->foreignId('proposed_by')->constrained('users');
            $table->foreignId('lcis_confirmed_by')->nullable()->constrained('users');
            $table->timestamp('lcis_confirmed_at')->nullable();
            $table->foreignId('amis_confirmed_by')->nullable()->constrained('users');
            $table->timestamp('amis_confirmed_at')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('withdrawn_by')->nullable()->constrained('users');
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('unmatched_by')->nullable()->constrained('users');
            $table->timestamp('unmatched_at')->nullable();
            $table->text('unmatch_reason')->nullable();
            $table->boolean('needs_review')->default(false)->index();
            $table->jsonb('review_reasons')->nullable();
            $table->char('lcis_hash_at_match', 64)->nullable();
            $table->char('amis_hash_at_match', 64)->nullable();
            $table->timestamps();
            $table->index(['status', 'updated_at']);
            $table->index('amis_contract_id');
        });

        DB::statement("CREATE UNIQUE INDEX project_matches_active_lcis_unique ON project_matches (lcis_project_id) WHERE status IN ('proposed', 'matched')");
        DB::statement("CREATE UNIQUE INDEX project_matches_active_amis_unique ON project_matches (amis_contract_id) WHERE status IN ('proposed', 'matched')");
    }

    public function down(): void
    {
        Schema::dropIfExists('project_matches');
    }
};
