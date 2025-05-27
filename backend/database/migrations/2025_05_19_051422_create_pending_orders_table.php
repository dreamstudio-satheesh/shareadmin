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
        Schema::create('pending_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zerodha_account_id')->constrained()->onDelete('cascade');
            $table->string('symbol');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('target_percent', 5, 2);
            $table->decimal('ltp_at_upload', 10, 2)->nullable();
            $table->decimal('target_price', 10, 2)->nullable();
            $table->decimal('stoploss_price', 10, 2)->nullable();
            $table->enum('status', ['pending', 'executed', 'failed', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->decimal('executed_price', 10, 2)->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('stoploss_triggered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_orders');
    }
};
