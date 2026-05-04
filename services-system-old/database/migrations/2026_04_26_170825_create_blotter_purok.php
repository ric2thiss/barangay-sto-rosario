<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('blotter_purok')) {
            Schema::create('blotter_purok', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('blotter_id');
                $table->unsignedBigInteger('purok_id')->nullable();
                $table->unsignedBigInteger('area_id')->nullable();
                $table->timestamps();

                $table->foreign('blotter_id')->references('blotter_id')->on('blotter_records')->onDelete('cascade');
                $table->foreign('purok_id')->references('purok_id')->on('puroks')->onDelete('set null');
                $table->foreign('area_id')->references('id')->on('incident_areas')->onDelete('set null');
            });
        } else {
            // Step 1: Migrate existing free-text incident_area values into the new table
            $existingAreas = DB::table('blotter_purok')
                ->whereNotNull('incident_area')
                ->where('incident_area', '!=', '')
                ->distinct()
                ->pluck('incident_area');

            foreach ($existingAreas as $areaName) {
                DB::table('incident_areas')->insertOrIgnore([
                    'name'       => trim($areaName),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Step 2: Add area_id column (nullable during migration)
            Schema::table('blotter_purok', function (Blueprint $table) {
                $table->unsignedBigInteger('area_id')->nullable()->after('purok_id');
                $table->foreign('area_id')->references('id')->on('incident_areas')->onDelete('set null');
            });

            // Step 3: Populate area_id from the now-populated incident_areas table
            $areaMap = DB::table('incident_areas')->pluck('id', 'name');

            DB::table('blotter_purok')
                ->whereNotNull('incident_area')
                ->where('incident_area', '!=', '')
                ->get()
                ->each(function ($row) use ($areaMap) {
                    $areaName = trim($row->incident_area);
                    if (isset($areaMap[$areaName])) {
                        DB::table('blotter_purok')
                            ->where('id', $row->id)
                            ->update(['area_id' => $areaMap[$areaName]]);
                    }
                });

            // Step 4: Drop the old string column
            Schema::table('blotter_purok', function (Blueprint $table) {
                $table->dropColumn('incident_area');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('blotter_purok', 'incident_area')) {
            // Restore the string column
            Schema::table('blotter_purok', function (Blueprint $table) {
                $table->string('incident_area', 255)->nullable()->after('purok_id');
            });

            // Backfill incident_area text from area_id
            $areas = DB::table('incident_areas')->pluck('name', 'id');

            DB::table('blotter_purok')
                ->whereNotNull('area_id')
                ->get()
                ->each(function ($row) use ($areas) {
                    if (isset($areas[$row->area_id])) {
                        DB::table('blotter_purok')
                            ->where('id', $row->id)
                            ->update(['incident_area' => $areas[$row->area_id]]);
                    }
                });

            // Drop area_id FK and column
            Schema::table('blotter_purok', function (Blueprint $table) {
                $table->dropForeign(['area_id']);
                $table->dropColumn('area_id');
            });
        }
        
        Schema::dropIfExists('incident_areas');
    }
};