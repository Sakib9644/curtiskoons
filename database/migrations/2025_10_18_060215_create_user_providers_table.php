<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProvidersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider');              // e.g., 'garmin', 'fitbit'
            $table->string('provider_user_id')->nullable(); // optional, from provider
            $table->text('access_token')->nullable();       // access token for provider
            $table->timestamps();

            // Ensure one provider per user
            $table->unique(['user_id', 'provider']);

            // Foreign key to users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_providers');
    }
}
