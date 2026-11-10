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
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'extra_charge_label')) {
                $table->string('extra_charge_label', 100)->nullable()->after('service_charge_amount');
            }
            if (! Schema::hasColumn('orders', 'extra_charge_amount')) {
                $table->decimal('extra_charge_amount', 10, 2)->default(0.00)->after('extra_charge_label');
            }
            if (! Schema::hasColumn('orders', 'bill_requested_at')) {
                $table->timestamp('bill_requested_at')->nullable()->after('payment_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = array_filter(
                ['extra_charge_label', 'extra_charge_amount', 'bill_requested_at'],
                fn (string $col): bool => Schema::hasColumn('orders', $col)
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

