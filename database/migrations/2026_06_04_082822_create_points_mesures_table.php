<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_mesures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('points_id')->constrained('points')->cascadeOnDelete();
            $table->dateTime('enregistre_le');
            $table->float('sht_temp')->nullable();
            $table->float('sht_hum')->nullable();
            $table->float('tmp_temp')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_mesures');
    }
};
