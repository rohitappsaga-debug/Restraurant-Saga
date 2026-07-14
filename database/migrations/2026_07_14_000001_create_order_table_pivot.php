<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_table', function (Blueprint $table) {
            $table->uuid('order_id');
            $table->uuid('table_id');
            $table->timestamps();

            $table->unique(['order_id', 'table_id']);
            $table->index('table_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_table');
    }
};
