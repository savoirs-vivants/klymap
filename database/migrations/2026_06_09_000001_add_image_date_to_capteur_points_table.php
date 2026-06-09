<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capteur_points', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
            $table->date('date')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('capteur_points', function (Blueprint $table) {
            $table->dropColumn(['image', 'date']);
        });
    }
};
