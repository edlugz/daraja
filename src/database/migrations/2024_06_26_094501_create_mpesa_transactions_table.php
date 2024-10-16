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
            $table->uuid('uuid');
            $table->integer('payment_id');
            $table->string('payment_reference');
            $table->string('short_code');
            $table->string('transaction_type');
            $table->string('account_number');
            $table->integer('amount');
            $table->integer('bill_reference')->nullable();
            $table->string('requester_name')->nullable();
            $table->string('requester_mobile')->nullable();
            $table->string('conversation_id')->nullable();
            $table->string('originator_conversation_id')->nullable();
            $table->string('response_code')->nullable();
            $table->string('response_description')->nullable();
            $table->string('result_type')->nullable();
            $table->string('result_code')->nullable();
            $table->string('result_description')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('transaction_completed_date_time')->nullable();
            $table->string('receiver_party_public_name')->nullable();
            $table->json('json_request')->nullable();
            $table->json('json_response')->nullable();
            $table->json('json_result')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
