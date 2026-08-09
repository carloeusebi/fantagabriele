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
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('setup');
            $table->unsignedSmallInteger('budget_per_participant')->default(500);
            $table->unsignedTinyInteger('slots_goalkeeper')->default(3);
            $table->unsignedTinyInteger('slots_defender')->default(8);
            $table->unsignedTinyInteger('slots_midfielder')->default(8);
            $table->unsignedTinyInteger('slots_forward')->default(6);
            $table->string('current_phase')->nullable();
            // FK added in a later migration once auction_participants/auction_calls exist (circular reference).
            $table->unsignedBigInteger('current_turn_auction_participant_id')->nullable();
            $table->unsignedBigInteger('current_call_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
