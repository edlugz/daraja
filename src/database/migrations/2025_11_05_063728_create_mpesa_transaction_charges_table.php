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
        Schema::create('mpesa_transaction_charges', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['business', 'mobile'])->default('mobile');
            $table->integer('min_amount');
            $table->integer('max_amount')->nullable();
            $table->integer('charge');
            $table->date('effective_date')->default(now());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_transaction_charges');
    }
};
