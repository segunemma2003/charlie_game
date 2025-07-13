<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_boosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('booster_type');
            $table->integer('quantity');
            $table->integer('power_multiplier')->default(100);
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('booster_type');
            $table->index(['user_id', 'booster_type']);
            $table->index('quantity');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_boosters');
    }
};
