<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->decimal('lat', 10, 8);
            $table->decimal('long', 11, 8);
            $table->float('icu')->nullable();

            $table->foreignId('temoin_id')->nullable()->constrained('points')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points');
    }

};
