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
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('buy_logic', ['fixed_percent', 'offset_ltp'])->default('fixed_percent');
            $table->decimal('buy_percent', 5, 2)->default(0.00);
            $table->decimal('stoploss_percent', 5, 2)->default(0.00);
            $table->time('auto_sell_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};
