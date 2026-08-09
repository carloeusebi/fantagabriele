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
        Schema::create('auction_viewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auction_participant_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['auction_id', 'user_id']);
        });

        // A participant may be claimed by at most one user. A plain UNIQUE
        // constraint can't express this with nullable auction_participant_id
        // (spectators all have NULL, which never collide anyway on most
        // drivers, but we still want the intent explicit) — a partial index
        // is the correct tool, supported by both pgsql and sqlite.
        DB::statement(
            'CREATE UNIQUE INDEX auction_viewers_participant_unique '.
            'ON auction_viewers (auction_id, auction_participant_id) WHERE auction_participant_id IS NOT NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_viewers');
    }
};
