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
        Schema::create('strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->unsignedTinyInteger('budget_goalkeeper_percent')->default(5);
            $table->unsignedTinyInteger('budget_defender_percent')->default(15);
            $table->unsignedTinyInteger('budget_midfielder_percent')->default(30);
            $table->unsignedTinyInteger('budget_forward_percent')->default(50);
            $table->json('locked_roles')->default('[]');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategies');
    }
};
