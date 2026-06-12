<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capteur_temoins', function (Blueprint $table) {
            $table->json('images')->nullable()->after('image');
        });

        Schema::table('capteur_points', function (Blueprint $table) {
            $table->json('images')->nullable()->after('image');
        });

        foreach (['capteur_temoins', 'capteur_points'] as $tableName) {
            DB::table($tableName)->whereNotNull('image')->where('image', '!=', '')->get(['id', 'image'])
                ->each(function ($row) use ($tableName) {
                    DB::table($tableName)->where('id', $row->id)->update([
                        'images' => json_encode([$row->image]),
                    ]);
                });
        }

        Schema::table('capteur_temoins', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('capteur_points', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }

    public function down(): void
    {
        Schema::table('capteur_temoins', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
        });

        Schema::table('capteur_points', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
        });

        foreach (['capteur_temoins', 'capteur_points'] as $tableName) {
            DB::table($tableName)->whereNotNull('images')->get(['id', 'images'])
                ->each(function ($row) use ($tableName) {
                    $images = json_decode($row->images, true) ?? [];
                    if (!empty($images)) {
                        DB::table($tableName)->where('id', $row->id)->update([
                            'image' => $images[0],
                        ]);
                    }
                });
        }

        Schema::table('capteur_temoins', function (Blueprint $table) {
            $table->dropColumn('images');
        });

        Schema::table('capteur_points', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
};
