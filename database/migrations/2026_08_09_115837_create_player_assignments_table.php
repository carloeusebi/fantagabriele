<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auction_participant_id')->constrained()->restrictOnDelete();
            $table->foreignId('player_id')->constrained()->restrictOnDelete();
            $table->foreignId('auction_call_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('price');
            $table->foreignId('replaces_assignment_id')->nullable()->constrained('player_assignments')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // A player may only be actively assigned once per auction. A plain UNIQUE
        // constraint can't express "active" with soft deletes (NULLs never collide),
        // so this is a partial index — supported by both pgsql and sqlite.
        DB::statement(
            'CREATE UNIQUE INDEX player_assignments_active_unique '.
            'ON player_assignments (auction_id, player_id) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_assignments');
    }
};
