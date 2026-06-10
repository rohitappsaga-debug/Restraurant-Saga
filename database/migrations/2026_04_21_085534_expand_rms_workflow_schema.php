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
        // 1. Audit Logs for sensitive actions
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('action'); // e.g., 'CANCELLATION', 'DISCOUNT', 'OVERRIDE'
            $table->string('target_type'); // e.g., 'order_items'
            $table->uuid('target_id');
            $table->json('payload')->nullable(); // metadata: {old, new, reason}
            $table->timestamps();
        });

        // 2. Table Sessions (The Sitting)
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('table_id')->index();
            $table->uuid('waiter_id')->index(); // Session owner
            $table->string('status')->default('active'); // active, closed
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        // 3. KOTs (Kitchen Order Tickets)
        Schema::create('kots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->index();
            $table->integer('batch_number')->default(1);
            $table->timestamp('sent_at')->useCurrent();
            $table->string('printer_status')->default('pending'); // pending, printed, failed
            $table->timestamps();
        });

        // 4. Schema Refinement for existing tables
        Schema::table('tables', function (Blueprint $table) {
            $table->uuid('current_session_id')->nullable()->after('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('session_id')->nullable()->after('id');
            $table->string('type')->default('dine-in'); // dine-in, takeaway, delivery
            $table->decimal('service_charge', 10, 2)->default(0.00);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->uuid('kot_id')->nullable()->after('order_id');
            $table->timestamp('served_at')->nullable();
            $table->uuid('served_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->uuid('cancelled_by')->nullable();
            $table->text('cancel_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['kot_id', 'served_at', 'served_by', 'cancelled_at', 'cancelled_by', 'cancel_reason']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['session_id', 'type', 'service_charge']);
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn('current_session_id');
        });

        Schema::dropIfExists('kots');
        Schema::dropIfExists('table_sessions');
        Schema::dropIfExists('audit_logs');
    }
};
