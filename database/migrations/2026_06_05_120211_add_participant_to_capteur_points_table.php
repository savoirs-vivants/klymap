<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capteur_points', function (Blueprint $table) {
            $table->foreignId('session_id')->nullable()->after('capteur_temoin_id')
                  ->constrained('campagnes')->nullOnDelete();
            $table->foreignId('participant_id')->nullable()->after('session_id')
                  ->constrained('session_participants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('capteur_points', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
            $table->dropForeign(['participant_id']);
            $table->dropColumn(['session_id', 'participant_id']);
        });
    }
};
