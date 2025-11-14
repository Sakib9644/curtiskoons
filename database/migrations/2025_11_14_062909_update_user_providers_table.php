<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_providers', function (Blueprint $table) {
            // Example: remove old unique constraint
            $table->dropUnique('user_providers_user_id_provider_unique');

            // Example: add new column
            // $table->string('new_column')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_providers', function (Blueprint $table) {
            // Restore unique constraint if needed
            $table->unique(['user_id', 'provider']);
            // Drop new column if added
            // $table->dropColumn('new_column');
        });
    }
};
