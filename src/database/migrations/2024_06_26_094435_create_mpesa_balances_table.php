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
        Schema::create('mpesa_balances', function (Blueprint $table) {
            $table->id();
            $table->string('short_code');
            $table->float('utility_account');
            $table->float('working_account');
            $table->float('uncleared_balance');
            $table->json('json_result');
            $table->timestamps();
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
