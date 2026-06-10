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
        Schema::table('capteurs', function (Blueprint $table) {
            $table->dropColumn('direction_vent');
            $table->float('indice_chaleur')->nullable();
            $table->float('debit_pluie')->nullable();
            $table->float('densite_air')->nullable();
            $table->float('evapotranspiration')->nullable();
        });

        Schema::table('mesures', function (Blueprint $table) {
            $table->dropColumn('direction_vent');
            $table->float('indice_chaleur')->nullable();
            $table->float('debit_pluie')->nullable();
            $table->float('densite_air')->nullable();
            $table->float('evapotranspiration')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('capteurs', function (Blueprint $table) {
            $table->string('direction_vent')->nullable();
            $table->dropColumn(['indice_chaleur', 'debit_pluie', 'densite_air', 'evapotranspiration']);
        });

        Schema::table('mesures', function (Blueprint $table) {
            $table->string('direction_vent')->nullable();
            $table->dropColumn(['indice_chaleur', 'debit_pluie', 'densite_air', 'evapotranspiration']);
        });
    }
};
