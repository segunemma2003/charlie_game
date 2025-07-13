<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('crypto_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique();
            $table->enum('crypto_currency', ['ETH', 'BTC', 'USDT', 'TON']);
            $table->decimal('amount_crypto', 20, 8);
            $table->decimal('amount_usd', 10, 2);
            $table->string('payment_address');
            $table->string('user_wallet_address');
            $table->enum('item_type', ['boosters', 'attribute']);
            $table->json('item_data');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'failed', 'expired']);
            $table->integer('confirmations')->default(0);
            $table->string('blockchain_tx_hash')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            // Indexes
            $table->index('telegram_user_id');
            $table->index('transaction_id');
            $table->index('status');
            $table->index('crypto_currency');
            $table->index('payment_address');
            $table->index('blockchain_tx_hash');
            $table->index(['status', 'expires_at']);
            $table->index(['telegram_user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('crypto_transactions');
    }
};
