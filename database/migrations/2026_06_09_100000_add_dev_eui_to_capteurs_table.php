<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capteurs', function (Blueprint $table) {
            $table->string('DevEui')->nullable()->unique()->after('UID');
        });
    }

    public function down(): void
    {
        Schema::table('capteurs', function (Blueprint $table) {
            $table->dropColumn('DevEui');
        });
    }
};
