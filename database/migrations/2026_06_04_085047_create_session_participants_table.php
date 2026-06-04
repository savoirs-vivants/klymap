<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_session')
                  ->constrained('campagnes')
                  ->onDelete('cascade');

            $table->integer('id_groupe');

            $table->string('pseudo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_participants');
    }
};
