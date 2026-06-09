<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('capteurs', function (Blueprint $table) {
            $table->id();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('long', 11, 8)->nullable();
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
        Schema::dropIfExists('capteurs');
    }
};
