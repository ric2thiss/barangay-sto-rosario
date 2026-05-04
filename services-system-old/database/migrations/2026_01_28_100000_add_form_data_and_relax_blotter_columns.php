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
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->string('form_id', 32)->nullable()->after('form_number');
            $table->json('form_data')->nullable()->after('purpose');
        });

        Schema::table('blotter_records', function (Blueprint $table) {
            $table->unsignedBigInteger('complainant_id')->nullable()->change();
            $table->unsignedBigInteger('respondent_id')->nullable()->change();
            $table->string('incident_type')->nullable()->change();
            $table->text('incident_details')->nullable()->change();
            $table->date('incident_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->dropColumn(['form_id', 'form_data']);
        });

        Schema::table('blotter_records', function (Blueprint $table) {
            $table->unsignedBigInteger('complainant_id')->nullable(false)->change();
            $table->unsignedBigInteger('respondent_id')->nullable(false)->change();
            $table->string('incident_type')->nullable(false)->change();
            $table->text('incident_details')->nullable(false)->change();
            $table->date('incident_date')->nullable(false)->change();
        });
    }
};
