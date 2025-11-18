<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpikeMetricsTable extends Migration
{
    public function up()
    {
        Schema::create('spike_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider_slug');
            $table->date('date');
            $table->float('hrv')->nullable();
            $table->float('rhr')->nullable();
            $table->float('sleep_hours')->nullable();
            $table->integer('steps')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'provider_slug', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('spike_metrics');
    }
}
