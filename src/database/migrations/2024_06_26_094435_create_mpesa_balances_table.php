<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mpesa_balances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('account_id')->nullable();
            $table->string('short_code');
            $table->bigInteger('utility_account');
            $table->bigInteger('working_account');
            $table->bigInteger('uncleared_balance');
            $table->json('json_result');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_balances');
    }
};
