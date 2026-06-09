<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('capteur_points', function (Blueprint $table) {
            $table->json('night_overrides')->nullable()->after('std_dev');
        });
    }
    public function down(): void {
        Schema::table('capteur_points', function (Blueprint $table) {
            $table->dropColumn('night_overrides');
        });
    }
};
