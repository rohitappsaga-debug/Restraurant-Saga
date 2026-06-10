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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('category', 50);
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->string('image_url', 255)->nullable();
            $table->boolean('available')->default(true);
            $table->integer('preparation_time')->default(0);
            $table->uuid('category_id')->nullable();
            $table->string('available_from')->nullable();
            $table->string('available_to')->nullable();
            $table->boolean('is_veg')->default(true);
            $table->string('availability_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
