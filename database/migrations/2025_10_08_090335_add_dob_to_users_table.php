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
        // First, drop the columns if they exist
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'date')) {
                $table->dropColumn('date');
            }

            if (Schema::hasColumn('users', 'sex')) {
                $table->dropColumn('sex');
            }
        });

        // Then, add them again
        Schema::table('users', function (Blueprint $table) {
            $table->date('date')->nullable()->after('name');
            $table->enum('sex', ['Male', 'Female', 'Others'])->nullable()->after('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['date', 'sex']);
        });
    }
};
