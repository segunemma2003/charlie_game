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
            $table->string('image_path');
            $table->string('token_id')->nullable(); // Blockchain token ID
            $table->string('contract_address')->nullable();

            // Game mechanics
            $table->integer('charlie_points');
            $table->integer('power_level')->default(100);
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary']);
            $table->json('attributes'); // 16 battle attributes
            $table->boolean('is_locked')->default(false);

            $table->timestamps();

            $table->index(['telegram_user_id', 'is_locked']);
            $table->index('rarity');
            $table->index('power_level');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pnft_cards');
    }
};
