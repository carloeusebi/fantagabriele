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
        Schema::table('auction_viewers', function (Blueprint $table) {
            $table->foreignId('strategy_id')->nullable()->after('auction_participant_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auction_viewers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('strategy_id');
        });
    }
};
