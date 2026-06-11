<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('capteurs_meteo', function (Blueprint $table) {
            $table->id();
            $table->string('UID')->nullable()->unique();
            $table->string('DevEui')->nullable()->unique();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('long', 11, 8)->nullable();
            $table->float('temp')->nullable();
            $table->float('hum')->nullable();
            $table->float('vitesse_vent')->nullable();
            $table->float('press_baro')->nullable();
            $table->float('pluie')->nullable();
            $table->float('indice_chaleur')->nullable();
            $table->float('debit_pluie')->nullable();
            $table->float('densite_air')->nullable();
            $table->float('evapotranspiration')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mesures', function (Blueprint $table) {
            $table->dropForeign(['capteur_id']);
            $table->foreign('capteur_id')->references('id')->on('capteurs')->onDelete('cascade');
        });

        Schema::dropIfExists('capteurs_meteo');
    }
};
