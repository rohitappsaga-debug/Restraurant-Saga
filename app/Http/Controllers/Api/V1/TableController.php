<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Http\Requests\TableRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\TableResource;
use App\Models\Table;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TableController extends ApiController
{
    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $tables = Table::orderBy('number')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->withExists(['currentOrder as has_ready_items' => function ($query) {
                $query->whereHas('orderItems', fn ($q) => $q->where('status', OrderStatus::READY));
            }])
            ->get();

        return $this->respond(TableResource::collection($tables));
    }

    public function show(Table $table): JsonResponse
    {
        $table->load('currentOrder.orderItems.menuItem');

        return $this->respond(new TableResource($table));
    }

    /**
     * The open order this table belongs to (used when a waiter taps an
     * occupied table). Self-heals stale pointers via the service.
     */
    public function openOrder(Table $table): JsonResponse
    {
        $order = $this->orderService->openOrderForTable($table);

        if (!$order) {
            return $this->respond(null, 'No open order for this table');
        }

        $order->load(['orderItems.menuItem', 'tables', 'paymentTransactions']);

        return $this->respond(new OrderResource($order));
    }

    /** Mark a table cleaned and ready for new guests. */
    public function markCleaned(Table $table): JsonResponse
    {
        if ($table->status !== TableStatus::CLEANING) {
            abort(422, 'Only tables in cleaning state can be marked as cleaned');
        }

        $table->update(['status' => TableStatus::FREE]);

        return $this->respond(new TableResource($table->fresh()), 'Table ready for new guests');
    }

    public function store(TableRequest $request): JsonResponse
    {
        $table = Table::create($request->validated());

        return $this->respondCreated(new TableResource($table), 'Table created');
    }

    public function update(TableRequest $request, Table $table): JsonResponse
    {
        $table->update($request->validated());

        return $this->respond(new TableResource($table->fresh()), 'Table updated');
    }

    public function destroy(Table $table): JsonResponse
    {
        if ($table->current_order_id) {
            abort(422, 'Cannot delete a table with an open order');
        }

        $table->delete();

        return $this->respondDeleted('Table deleted');
    }

    /** Generic status change (free/occupied/reserved/cleaning/out_of_service). */
    public function updateStatus(Request $request, Table $table): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(TableStatus::class)],
        ]);

        $table->update(['status' => $validated['status']]);

        return $this->respond(new TableResource($table->fresh()), 'Table status updated');
    }

    /** Create a run of tables in one shot; existing numbers are skipped. */
    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_number' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1|max:100',
            'capacity' => 'required|integer|min:1',
        ]);

        $created = [];
        DB::transaction(function () use ($validated, &$created) {
            for ($i = 0; $i < $validated['quantity']; $i++) {
                $number = $validated['start_number'] + $i;
                if (Table::where('number', $number)->exists()) {
                    continue;
                }
                $created[] = Table::create([
                    'number' => $number,
                    'capacity' => $validated['capacity'],
                    'status' => TableStatus::FREE,
                ]);
            }
        });

        return $this->respondCreated(
            TableResource::collection($created),
            count($created) . ' table(s) created'
        );
    }

    /** Join two or more free tables under one group with a designated primary. */
    public function group(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_ids' => 'required|array|min:2',
            'table_ids.*' => 'uuid|exists:tables,id',
            'primary_id' => 'required|uuid|in_array:table_ids.*',
        ]);

        $groupId = (string) Str::uuid();

        DB::transaction(function () use ($validated, $groupId) {
            $tables = Table::whereIn('id', $validated['table_ids'])->lockForUpdate()->get();

            foreach ($tables as $table) {
                if ($table->status !== TableStatus::FREE || $table->group_id) {
                    throw new \DomainException("Table {$table->number} is not free to group.");
                }
            }

            Table::whereIn('id', $validated['table_ids'])->update(['group_id' => $groupId, 'is_primary' => false]);
            Table::where('id', $validated['primary_id'])->update(['is_primary' => true]);
        });

        $tables = Table::where('group_id', $groupId)->orderBy('number')->get();

        return $this->respond(TableResource::collection($tables), 'Tables grouped');
    }

    public function ungroup(Request $request): JsonResponse
    {
        $validated = $request->validate(['group_id' => 'required|uuid']);

        $count = Table::where('group_id', $validated['group_id'])
            ->update(['group_id' => null, 'is_primary' => false]);

        if ($count === 0) {
            abort(404, 'No tables found for that group');
        }

        return $this->respond(null, 'Tables ungrouped');
    }
}
