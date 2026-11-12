<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per sync of a source, shown on the admin sync status page.
     */
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 16);
            $table->string('status', 16);
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('expected_count')->nullable();
            $table->unsignedInteger('fetched_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);
            $table->unsignedInteger('missing_count')->default(0);
            $table->unsignedInteger('restored_count')->default(0);
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->jsonb('details')->nullable();
            $table->timestamps();
            $table->index(['source', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
