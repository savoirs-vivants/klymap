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
        Schema::table('capteur_points', function (Blueprint $table) {
            $table->boolean('icu_ack')->default(false)->after('std_dev');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('capteur_points', function (Blueprint $table) {
            $table->dropColumn('icu_ack');
        });
    }
};
