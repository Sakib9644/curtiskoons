<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('static_contents', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // 'terms_of_service', 'privacy_policy', 'phi_consent'
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('static_contents');
    }
};
