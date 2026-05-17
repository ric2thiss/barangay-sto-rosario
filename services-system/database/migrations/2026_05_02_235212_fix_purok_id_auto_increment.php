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
    DB::statement('ALTER TABLE puroks MODIFY purok_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (purok_id)');
    
    DB::table('puroks')->where('purok_id', 0)->delete();
}

public function down(): void
{
    DB::statement('ALTER TABLE puroks MODIFY purok_id BIGINT UNSIGNED NOT NULL');
}
};
