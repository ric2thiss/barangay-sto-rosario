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
        Schema::create('residents_import_temp', function (Blueprint $table) {
            $table->id('temp_id');
            $table->string('full_name_raw')->nullable();
            $table->string('age_raw')->nullable();
            $table->string('sex_raw')->nullable();
            $table->string('address_raw')->nullable();
            $table->string('purok_raw')->nullable();
            $table->string('source_file')->nullable();
            $table->integer('estimated_age_years')->nullable();
            $table->boolean('is_estimated')->default(false);
            $table->string('import_status')->default('VALID'); // VALID / NEEDS_REVIEW
            $table->text('remarks')->nullable();
            $table->timestamp('imported_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residents_import_temp');
    }
};
