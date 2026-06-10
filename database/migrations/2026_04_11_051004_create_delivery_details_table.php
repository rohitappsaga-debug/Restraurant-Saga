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
        Schema::create('delivery_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('address');
            $table->uuid('driver_id')->nullable();
            $table->string('delivery_status')->default('pending');
            $table->timestamps(); // implied since there wasn't a strict created at? Prisma schema often misses it if no explicit mapping
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_details');
    }
};
