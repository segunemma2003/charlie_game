<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('telegram_users');
            $table->foreignId('buyer_id')->nullable()->constrained('telegram_users');
            $table->enum('item_type', ['pnft_card', 'booster', 'attribute']);
            $table->unsignedBigInteger('item_id');
            $table->bigInteger('price');
            $table->enum('status', ['active', 'sold', 'cancelled', 'expired']);
            $table->datetime('expires_at');
            $table->timestamps();

            // Indexes
            $table->index('seller_id');
            $table->index('buyer_id');
            $table->index('item_type');
            $table->index('item_id');
            $table->index('status');
            $table->index('price');
            $table->index('expires_at');
            $table->index(['status', 'expires_at']);
            $table->index(['item_type', 'status']);
            $table->index(['seller_id', 'status']);
            $table->index(['price', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_listings');
    }
};
