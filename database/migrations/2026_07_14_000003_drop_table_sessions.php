<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'session_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('session_id');
            });
        }

        if (Schema::hasColumn('tables', 'current_session_id')) {
            Schema::table('tables', function (Blueprint $table) {
                $table->dropColumn('current_session_id');
            });
        }

        Schema::dropIfExists('table_sessions');
    }

    public function down(): void
    {
        // Structure only — session data dropped in up() is not recoverable.
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('table_id');
            $table->uuid('waiter_id');
            $table->string('status')->default('active');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('session_id')->nullable();
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->uuid('current_session_id')->nullable();
        });
    }
};
