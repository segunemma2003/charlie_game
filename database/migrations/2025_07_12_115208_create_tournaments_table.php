<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['bracket_single', 'bracket_double', 'league', 'leaderboard', 'buy_in', 'special_event', 'guild']);
            $table->enum('format', ['elimination', 'round_robin', 'swiss']);
            $table->bigInteger('entry_fee')->default(0);
            $table->bigInteger('prize_pool')->default(0);
            $table->integer('max_participants');
            $table->datetime('start_time');
            $table->datetime('end_time');
            $table->enum('status', ['upcoming', 'active', 'completed', 'cancelled']);
            $table->json('rules')->nullable();
            $table->enum('skill_level_required', ['any', 'beginner', 'intermediate', 'advanced', 'expert'])->default('any');
            $table->string('banner_path')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('status');
            $table->index('skill_level_required');
            $table->index('start_time');
            $table->index('end_time');
            $table->index('entry_fee');
            $table->index('prize_pool');
            $table->index(['status', 'start_time']);
            $table->index(['type', 'status']);
            $table->index(['skill_level_required', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tournaments');
    }
};
