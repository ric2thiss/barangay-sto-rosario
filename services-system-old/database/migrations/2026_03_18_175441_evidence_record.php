<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->string('evidence_pic')->nullable()->after('incident_date');
            $table->string('evidence_link')->nullable()->after('evidence_pic');
        });
    }

    public function down(): void
    {
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->dropColumn(['evidence_pic', 'evidence_link']);
        });
    }
};