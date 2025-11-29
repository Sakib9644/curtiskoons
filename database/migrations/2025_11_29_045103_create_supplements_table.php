<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplements', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // e.g., "Omega-3 Fish Oil"
            $table->text('dosage')->nullable();  // e.g., "1000mg • Morning"
            $table->text('description')->nullable(); // optional notes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplements');
    }
};
