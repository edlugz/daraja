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
        Schema::create('mpesa_fundings', function (Blueprint $table) {
            $table->id();
            $table->integer('fund_id');
            $table->string('mobile_no');
            $table->string('amount');
            $table->string('bill_reference');
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->string('response_code')->nullable();
            $table->string('response_description')->nullable();
            $table->string('result_code')->nullable();
            $table->string('result_desc')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('transaction_date')->nullable();
            $table->string('json_request')->nullable();
            $table->string('json_response')->nullable();
            $table->string('json_result')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_fundings');
    }
};
