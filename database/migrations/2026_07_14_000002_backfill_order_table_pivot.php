<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders that went through a session: take the session's table
        if (Schema::hasTable('table_sessions') && Schema::hasColumn('orders', 'session_id')) {
            DB::statement(<<<'SQL'
                INSERT INTO order_table (order_id, table_id, created_at, updated_at)
                SELECT o.id, ts.table_id, NOW(), NOW()
                FROM orders o
                JOIN table_sessions ts ON ts.id = o.session_id
                ON CONFLICT (order_id, table_id) DO NOTHING
            SQL);

            // Session-less orders: fall back to their table_number
            DB::statement(<<<'SQL'
                INSERT INTO order_table (order_id, table_id, created_at, updated_at)
                SELECT o.id, t.id, NOW(), NOW()
                FROM orders o
                JOIN tables t ON t.number = o.table_number
                WHERE o.session_id IS NULL AND o.table_number IS NOT NULL
                ON CONFLICT (order_id, table_id) DO NOTHING
            SQL);

            // Point occupied tables at their active session's open order
            DB::statement(<<<'SQL'
                UPDATE tables t
                SET current_order_id = sub.order_id
                FROM (
                    SELECT DISTINCT ON (ts.table_id) ts.table_id, o.id AS order_id
                    FROM table_sessions ts
                    JOIN orders o ON o.session_id = ts.id
                    WHERE ts.status = 'active'
                      AND o.is_paid = false
                      AND o.status != 'cancelled'
                    ORDER BY ts.table_id, o.created_at DESC
                ) sub
                WHERE t.id = sub.table_id
                  AND t.current_session_id IS NOT NULL
            SQL);
        }
    }

    public function down(): void
    {
        // One-way backfill: rows derived from sessions are indistinguishable
        // from organic rows, so down() just clears the pivot.
        DB::table('order_table')->truncate();
    }
};
