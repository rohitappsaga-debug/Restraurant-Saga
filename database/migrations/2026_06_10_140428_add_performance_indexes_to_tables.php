<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('is_paid');
            $table->index('created_at');
            $table->index(['table_number', 'status']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index('status');
            $table->index(['order_id', 'status']);
        });

        Schema::table('table_sessions', function (Blueprint $table) {
            $table->index('status');
            $table->index(['table_id', 'status']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read']);
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('available');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['is_paid']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['table_number', 'status']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['order_id', 'status']);
        });

        Schema::table('table_sessions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['table_id', 'status']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'read']);
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['available']);
        });
    }
};
