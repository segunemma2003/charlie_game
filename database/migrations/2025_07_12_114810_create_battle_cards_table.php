<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('battle_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battle_id')->constrained()->onDelete('cascade');
            $table->foreignId('pnft_card_id')->constrained();
            $table->foreignId('telegram_user_id')->constrained();
            $table->integer('round_number');
            $table->json('boosters_used')->nullable();
            $table->enum('result', ['win', 'loss', 'pending']);
            $table->integer('damage_dealt')->default(0);
            $table->integer('damage_received')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('battle_id');
            $table->index('pnft_card_id');
            $table->index('telegram_user_id');
            $table->index('round_number');
            $table->index('result');
            $table->index(['battle_id', 'round_number']);
            $table->index(['battle_id', 'telegram_user_id']);
            $table->index(['telegram_user_id', 'result']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('battle_cards');
    }
};
