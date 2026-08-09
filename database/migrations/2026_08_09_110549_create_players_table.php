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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fanta_id')->unique();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('role');
            $table->string('role_mantra')->nullable();
            $table->unsignedSmallInteger('initial_quotation');
            $table->unsignedSmallInteger('current_quotation');
            $table->unsignedSmallInteger('initial_quotation_mantra')->nullable();
            $table->unsignedSmallInteger('current_quotation_mantra')->nullable();
            $table->unsignedSmallInteger('fvm')->nullable();
            $table->unsignedSmallInteger('fvm_mantra')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
