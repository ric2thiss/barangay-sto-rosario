<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('certificate_requests', 'payment_status')) {
                $table->enum('payment_status', ['Paid', 'Pending'])->default('Pending')->after('status');
            }

            if (! Schema::hasColumn('certificate_requests', 'amount_due')) {
                $table->decimal('amount_due', 10, 2)->nullable()->after('payment_status');
            }

            if (! Schema::hasColumn('certificate_requests', 'bir_tax')) {
                $table->decimal('bir_tax', 10, 2)->nullable()->after('amount_due');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_requests', 'bir_tax')) {
                $table->dropColumn('bir_tax');
            }

            if (Schema::hasColumn('certificate_requests', 'amount_due')) {
                $table->dropColumn('amount_due');
            }

            if (Schema::hasColumn('certificate_requests', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
