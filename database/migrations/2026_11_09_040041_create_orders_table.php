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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_table_id')->constrained('restaurant_tables')->restrictOnDelete();
            $table->uuid('session_token')->index();
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20)->index();
            $table->enum('order_status', [
                'pending_confirmation',
                'confirmed',
                'preparing',
                'served',
                'settled',
                'cancelled',
            ])->default('pending_confirmation')->index();
            $table->decimal('gross_subtotal', 10, 2)->default(0.00);
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->foreignId('discount_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('vat_rate', 5, 2)->default(0.00);
            $table->decimal('vat_amount', 10, 2)->default(0.00);
            $table->decimal('service_charge_rate', 5, 2)->default(0.00);
            $table->decimal('service_charge_amount', 10, 2)->default(0.00);
            $table->decimal('grand_total', 10, 2)->default(0.00);
            $table->enum('payment_method', ['cash', 'card', 'online', 'unpaid'])->default('unpaid');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
