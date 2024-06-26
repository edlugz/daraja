<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('payment_id');
            $table->string('payment_reference');
            $table->string('short_code');
            $table->string('transaction_type');
            $table->string('account_number');
            $table->integer('amount');
            $table->integer('bill_reference')->nullable();
            $table->string('requester_name')->nullable();
            $table->string('requester_mobile')->nullable();
            $table->json('json_request')->nullable();
            $table->json('json_response')->nullable();
            $table->json('json_result')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};
