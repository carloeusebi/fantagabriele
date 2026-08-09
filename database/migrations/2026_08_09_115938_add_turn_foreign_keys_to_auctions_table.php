<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->foreign('current_turn_auction_participant_id')
                ->references('id')->on('auction_participants')
                ->nullOnDelete();

            $table->foreign('current_call_id')
                ->references('id')->on('auction_calls')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropForeign(['current_turn_auction_participant_id']);
            $table->dropForeign(['current_call_id']);
        });
    }
};
