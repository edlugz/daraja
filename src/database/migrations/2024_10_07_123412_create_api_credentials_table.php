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
        Schema::create('api_credentials', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('account_id');
            $table->string('short_code')->nullable();
            $table->string('initiator')->nullable();
            $table->string('initiator_password')->nullable();
            $table->string('consumer_key')->nullable();
            $table->string('consumer_secret')->nullable();
            $table->string('balance_result_url')->nullable();
            $table->boolean('api_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_credentials');
    }
};
