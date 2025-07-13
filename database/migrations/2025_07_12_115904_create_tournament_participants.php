<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tournament_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('ranking')->nullable();
            $table->bigInteger('points')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('tournament_id');
            $table->index('user_id');
            $table->index('ranking');
            $table->index('points');
            $table->index(['tournament_id', 'user_id']);
            $table->index(['tournament_id', 'ranking']);
            $table->index(['tournament_id', 'points']);
            $table->unique(['tournament_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tournament_participants');
    }
};
