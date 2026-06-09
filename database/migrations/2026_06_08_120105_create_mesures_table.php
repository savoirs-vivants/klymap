<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mesures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('capteur_id')->constrained()->onDelete('cascade');

            $table->float('temp')->nullable();
            $table->float('hum')->nullable();
            $table->float('vitesse_vent')->nullable();
            $table->string('direction_vent')->nullable();
            $table->float('press_baro')->nullable();
            $table->float('pluie')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mesures');
    }
};
