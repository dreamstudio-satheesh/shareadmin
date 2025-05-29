<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zerodha_account_id')->constrained()->onDelete('cascade');
            $table->string('symbol');
            $table->decimal('quantity', 12, 2);
            $table->decimal('average_price', 12, 2)->nullable();
            $table->decimal('last_price', 12, 2)->nullable();
            $table->decimal('pnl', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['zerodha_account_id', 'symbol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
