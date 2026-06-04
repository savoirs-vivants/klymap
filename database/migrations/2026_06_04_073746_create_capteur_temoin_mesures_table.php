<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capteur_temoin_mesures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capteur_temoin_id')->constrained()->cascadeOnDelete();
            $table->dateTime('enregistre_le');
            $table->decimal('sht_temp', 6, 2)->nullable();
            $table->decimal('sht_hum', 6, 2)->nullable();
            $table->decimal('tmp_temp', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capteur_temoin_mesures');
    }
};
