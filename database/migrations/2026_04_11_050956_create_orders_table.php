<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('table_number')->nullable();
            $table->string('status')->default('pending');
            $table->uuid('created_by');
            $table->decimal('total', 10, 2)->default(0);
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 10, 2)->default(0)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->string('payment_method')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->boolean('hold_status')->default(false);
            // order_number is bigSerial effectively via autoIncrement on PostgreSQL? No, autoIncrement() adds primary key. 
            // Better to use generic integer or bigInteger and use raw or sequence. Let's make it standard integer.
            $table->unsignedBigInteger('order_number')->unique(); 
            $table->uuid('parent_order_id')->nullable();
            $table->timestamps();
        });

        // Auto-incrementing order_number without making it the primary key (driver-specific DDL)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE SEQUENCE orders_order_number_seq OWNED BY orders.order_number');
            DB::statement("ALTER TABLE orders ALTER COLUMN order_number SET DEFAULT nextval('orders_order_number_seq')");
        } elseif (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE orders MODIFY order_number BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
