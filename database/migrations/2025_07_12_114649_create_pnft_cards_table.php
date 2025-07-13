<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pnft_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('image_path'); // Local file path
            $table->string('token_id')->nullable(); // Blockchain token ID
            $table->string('contract_address')->nullable(); // Smart contract address
            $table->integer('charlie_points');
            $table->json('attributes');
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary']);
            $table->boolean('is_locked')->default(false);
            $table->integer('power_level')->default(100);
            $table->timestamps();

            // Indexes
            $table->index('telegram_user_id');
            $table->index('rarity');
            $table->index('charlie_points');
            $table->index('power_level');
            $table->index('is_locked');
            $table->index('token_id');
            $table->index(['telegram_user_id', 'is_locked']);
            $table->index(['rarity', 'charlie_points']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pnft_cards');
    }
};
