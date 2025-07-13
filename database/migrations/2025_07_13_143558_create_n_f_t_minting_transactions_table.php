<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('nft_minting_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnft_card_id')->constrained()->onDelete('cascade');
            $table->foreignId('telegram_user_id')->constrained();

            $table->string('recipient_address');
            $table->enum('network', ['ethereum', 'polygon', 'goerli']);
            $table->enum('status', ['pending', 'minting', 'completed', 'failed'])->default('pending');

            $table->string('transaction_hash')->nullable();
            $table->string('token_id')->nullable();
            $table->string('ipfs_image_hash')->nullable();
            $table->string('ipfs_metadata_hash')->nullable();
            $table->text('metadata_uri')->nullable();

            $table->integer('blockchain_confirmations')->default(0);
            $table->integer('gas_used')->nullable();
            $table->json('blockchain_data')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['telegram_user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('nft_minting_transactions');
    }
};
