<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existing = collect(DB::select("
            SELECT CONSTRAINT_NAME, TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME IS NOT NULL
              AND CONSTRAINT_SCHEMA = database()
        "))->map(function ($r) {
            return $r->CONSTRAINT_NAME;
        })->all();

        Schema::table('users', function (Blueprint $table) use ($existing) {
            if (Schema::hasColumn('users', 'role_id') && ! in_array('users_role_id_foreign', $existing)) {
                $table->foreign('role_id')->references('role_id')->on('roles')->onDelete('restrict');
            }
        });
        Schema::table('residents', function (Blueprint $table) use ($existing) {
            if (Schema::hasColumn('residents', 'purok_id') && ! in_array('residents_purok_id_foreign', $existing)) {
                $table->foreign('purok_id')->references('purok_id')->on('puroks')->onDelete('restrict');
            }
        });
        Schema::table('certificate_requests', function (Blueprint $table) use ($existing) {
            if (Schema::hasColumn('certificate_requests', 'resident_id') && ! in_array('certificate_requests_resident_id_foreign', $existing)) {
                $table->foreign('resident_id')->references('resident_id')->on('residents')->onDelete('restrict');
            }
            if (Schema::hasColumn('certificate_requests', 'certificate_type_id') && ! in_array('certificate_requests_certificate_type_id_foreign', $existing)) {
                $table->foreign('certificate_type_id')->references('certificate_type_id')->on('certificate_types')->onDelete('restrict');
            }
            if (Schema::hasColumn('certificate_requests', 'processed_by') && ! in_array('certificate_requests_processed_by_foreign', $existing)) {
                $table->foreign('processed_by')->references('user_id')->on('users')->onDelete('restrict');
            }
        });
        Schema::table('blotter_records', function (Blueprint $table) use ($existing) {
            if (Schema::hasColumn('blotter_records', 'complainant_id') && ! in_array('blotter_records_complainant_id_foreign', $existing)) {
                $table->foreign('complainant_id')->references('resident_id')->on('residents')->onDelete('restrict');
            }
            if (Schema::hasColumn('blotter_records', 'respondent_id') && ! in_array('blotter_records_respondent_id_foreign', $existing)) {
                $table->foreign('respondent_id')->references('resident_id')->on('residents')->onDelete('restrict');
            }
            if (Schema::hasColumn('blotter_records', 'recorded_by') && ! in_array('blotter_records_recorded_by_foreign', $existing)) {
                $table->foreign('recorded_by')->references('user_id')->on('users')->onDelete('restrict');
            }
        });
        Schema::table('activity_logs', function (Blueprint $table) use ($existing) {
            if (Schema::hasColumn('activity_logs', 'user_id') && ! in_array('activity_logs_user_id_foreign', $existing)) {
                $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });
     
      
       
     
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropForeign(['resident_id']);
            $table->dropForeign(['certificate_type_id']);
            $table->dropForeign(['processed_by']);
        });
      
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->dropForeign(['complainant_id']);
            $table->dropForeign(['respondent_id']);
            $table->dropForeign(['recorded_by']);
        });
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
