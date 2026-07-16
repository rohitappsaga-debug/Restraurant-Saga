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
            $isPgsql = DB::connection()->getDriverName() === 'pgsql';
            $ignoreKeyword = $isPgsql ? '' : 'IGNORE';
            $onConflict = $isPgsql ? 'ON CONFLICT DO NOTHING' : '';

            DB::statement("
                INSERT {$ignoreKeyword} INTO order_table (order_id, table_id, created_at, updated_at)
                SELECT o.id, ts.table_id, NOW(), NOW()
                FROM orders o
                JOIN table_sessions ts ON ts.id = o.session_id
                {$onConflict}
            ");

            // Session-less orders: fall back to their table_number
            DB::statement("
                INSERT {$ignoreKeyword} INTO order_table (order_id, table_id, created_at, updated_at)
                SELECT o.id, t.id, NOW(), NOW()
                FROM orders o
                JOIN tables t ON t.number = o.table_number
                WHERE o.session_id IS NULL AND o.table_number IS NOT NULL
                {$onConflict}
            ");

            // Point occupied tables at their active session's open order
            $activeSessions = DB::table('table_sessions')
                ->where('status', 'active')
                ->get();
                
            foreach ($activeSessions as $session) {
                $latestOrder = DB::table('orders')
                    ->where('session_id', $session->id)
                    ->where('is_paid', false)
                    ->where('status', '!=', 'cancelled')
                    ->orderByDesc('created_at')
                    ->first();
                    
                if ($latestOrder) {
                    DB::table('tables')
                        ->where('id', $session->table_id)
                        ->whereNotNull('current_session_id')
                        ->update(['current_order_id' => $latestOrder->id]);
                }
            }
        }
    }

    public function down(): void
    {
        // One-way backfill: rows derived from sessions are indistinguishable
        // from organic rows, so down() just clears the pivot.
        DB::table('order_table')->truncate();
    }
};
