<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blotter_records', function (Blueprint $table) {
            if (!$this->hasIndex('blotter_records', 'blotter_records_complainant_id_index')) {
                $table->index('complainant_id');
            }
            if (!$this->hasIndex('blotter_records', 'blotter_records_respondent_id_index')) {
                $table->index('respondent_id');
            }
            if (!$this->hasIndex('blotter_records', 'blotter_records_recorded_by_index')) {
                $table->index('recorded_by');
            }
            if (!$this->hasIndex('blotter_records', 'blotter_records_incident_date_index')) {
                $table->index('incident_date');
            }
            if (!$this->hasIndex('blotter_records', 'blotter_records_status_index')) {
                $table->index('status');
            }
        });

        Schema::table('summons', function (Blueprint $table) {
            if (!$this->hasIndex('summons', 'summons_blotter_id_index')) {
                $table->index('blotter_id');
            }
            if (!$this->hasIndex('summons', 'summons_respondent_id_index')) {
                $table->index('respondent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->dropIndex(['complainant_id']);
            $table->dropIndex(['respondent_id']);
            $table->dropIndex(['recorded_by']);
            $table->dropIndex(['incident_date']);
            $table->dropIndex(['status']);
        });

        Schema::table('summons', function (Blueprint $table) {
            $table->dropIndex(['blotter_id']);
            $table->dropIndex(['respondent_id']);
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
