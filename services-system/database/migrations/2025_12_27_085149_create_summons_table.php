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
        Schema::create('summons', function (Blueprint $table) {
            $table->id('summon_id');
            $table->unsignedBigInteger('blotter_id');
            $table->unsignedBigInteger('respondent_id');
            $table->date('summon_date');
            $table->date('hearing_date');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('summons');
    }
};
