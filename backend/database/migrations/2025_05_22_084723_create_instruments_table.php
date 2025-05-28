<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('instrument_token')->unique();
            $table->string('exchange', 50);
            $table->string('tradingsymbol');
            $table->string('name')->nullable();
            $table->decimal('last_price', 10, 2)->nullable();
            $table->date('expiry')->nullable();
            $table->decimal('strike', 10, 2)->nullable();
            $table->string('instrument_type', 50)->nullable();
            $table->string('segment', 50)->nullable();
            $table->integer('lot_size')->nullable();
            $table->decimal('tick_size', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instruments');
    }
};
