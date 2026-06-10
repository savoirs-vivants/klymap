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

        // Duplication des données existantes de `capteurs` vers `capteurs_meteo`
        DB::statement('
            INSERT INTO capteurs_meteo (id, UID, DevEui, lat, `long`, temp, hum, vitesse_vent, press_baro, pluie, indice_chaleur, debit_pluie, densite_air, evapotranspiration, created_at, updated_at)
            SELECT id, UID, DevEui, lat, `long`, temp, hum, vitesse_vent, press_baro, pluie, indice_chaleur, debit_pluie, densite_air, evapotranspiration, created_at, updated_at
            FROM capteurs
        ');

        // `mesures.capteur_id` référence désormais `capteurs_meteo` (les nouveaux capteurs
        // ne seront créés que dans capteurs_meteo).
        Schema::table('mesures', function (Blueprint $table) {
            $table->dropForeign(['capteur_id']);
            $table->foreign('capteur_id')->references('id')->on('capteurs_meteo')->onDelete('cascade');
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
