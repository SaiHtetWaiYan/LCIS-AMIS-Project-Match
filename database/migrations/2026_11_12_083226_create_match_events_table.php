<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only audit log of everything that happened to a match. user_id is null for
     * changes made by the system (e.g. flagged for review after a sync).
     */
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('side', 16)->nullable();
            $table->string('action', 32);
            $table->string('from_status', 16)->nullable();
            $table->string('to_status', 16);
            $table->text('reason')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
