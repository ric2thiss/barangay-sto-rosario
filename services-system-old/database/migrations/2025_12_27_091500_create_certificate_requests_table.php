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
        if (! Schema::hasTable('certificate_requests')) {
            Schema::create('certificate_requests', function (Blueprint $table) {
                $table->id('request_id');
                $table->unsignedBigInteger('resident_id');
                $table->unsignedBigInteger('certificate_type_id');
                $table->string('purpose');
                $table->string('status')->default('Pending');
                $table->string('requested_by')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->date('date_requested');
                $table->date('date_released')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_requests');
    }
};
