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
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('tax_rate', 5, 2)->default(5.00);
            $table->string('currency', 10)->default('₹');
            $table->string('restaurant_name', 100)->default('Restaurant');
            $table->json('discount_presets')->nullable();
            $table->json('printer_config')->nullable();
            $table->json('business_hours')->nullable();
            $table->json('enabled_payment_methods')->nullable();
            $table->string('receipt_footer')->default('Thank you for your business!');
            $table->string('gst_no', 50)->nullable();
            $table->text('restaurant_address')->nullable();
            $table->boolean('tax_enabled')->default(true);
            $table->json('notification_preferences')->nullable();
            $table->integer('reservation_grace_period')->default(15);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
