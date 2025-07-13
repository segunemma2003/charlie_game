<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_id')->unique();
            $table->string('username')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('wallet_address')->nullable();
            $table->bigInteger('charlie_points')->default(0);
            $table->bigInteger('moon_pot_points')->default(0);
            $table->integer('total_wins')->default(0);
            $table->integer('total_losses')->default(0);
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('beginner');
            $table->json('character_attributes')->nullable();
            $table->enum('battle_style_preference', ['funny', 'hardcore'])->default('funny');
            $table->timestamps();

            // Indexes
            $table->index('telegram_id');
            $table->index('wallet_address');
            $table->index('skill_level');
            $table->index('charlie_points');
            $table->index('total_wins');
            $table->index(['total_wins', 'total_losses']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('telegram_users');
    }
};
