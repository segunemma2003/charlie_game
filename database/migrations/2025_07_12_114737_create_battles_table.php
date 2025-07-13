<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('battles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player1_id')->constrained('telegram_telegram_users');
            $table->foreignId('player2_id')->constrained('telegram_telegram_users');
            $table->foreignId('winner_id')->nullable()->constrained('telegram_telegram_users');
            $table->enum('battle_type', ['pvp', 'pve', 'tournament']);
            $table->enum('battle_style', ['funny', 'hardcore']);
            $table->integer('card_count');
            $table->bigInteger('total_pot');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled']);
            $table->json('round_results')->nullable();
            $table->boolean('is_risk_mode')->default(false);
            $table->foreignId('tournament_id')->nullable()->constrained();
            $table->string('transaction_hash')->nullable(); // Blockchain transaction
            $table->timestamps();

            // Indexes
            $table->index('player1_id');
            $table->index('player2_id');
            $table->index('winner_id');
            $table->index('battle_type');
            $table->index('status');
            $table->index('tournament_id');
            $table->index('transaction_hash');
            $table->index(['status', 'created_at']);
            $table->index(['player1_id', 'status']);
            $table->index(['player2_id', 'status']);
            $table->index(['battle_type', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('battles');
    }
};
