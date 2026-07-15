<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\PurchaseOrderItemRequest;
use App\Http\Requests\Api\PurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderItemResource;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\Ingredient;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $orders = PurchaseOrder::with('supplier')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('supplier_id'), fn ($q, $id) => $q->where('supplier_id', $id))
            ->latest()
            ->paginate(min((int) $request->query('limit', 20), 100));

        return $this->respond(PurchaseOrderResource::collection($orders));
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return $this->respond(new PurchaseOrderResource($purchaseOrder->load(['supplier', 'items.ingredient'])));
    }

    public function store(PurchaseOrderRequest $request): JsonResponse
    {
        $order = PurchaseOrder::create($request->validated());

        return $this->respondCreated(new PurchaseOrderResource($order->load('supplier')), 'Purchase order created');
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->update($request->validated());

        return $this->respond(new PurchaseOrderResource($purchaseOrder->fresh(['supplier', 'items'])), 'Purchase order updated');
    }

    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->delete();

        return $this->respondDeleted('Purchase order deleted');
    }

    /** Add a line item and keep the PO total in sync. */
    public function addItem(PurchaseOrderItemRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status === 'received') {
            abort(422, 'Cannot modify a received purchase order');
        }

        $item = DB::transaction(function () use ($request, $purchaseOrder) {
            $item = $purchaseOrder->items()->create($request->validated());
            $this->recalculateTotal($purchaseOrder);

            return $item;
        });

        return $this->respondCreated(new PurchaseOrderItemResource($item->load('ingredient')), 'Line item added');
    }

    public function removeItem(PurchaseOrder $purchaseOrder, PurchaseOrderItem $item): JsonResponse
    {
        abort_unless($item->purchase_order_id === $purchaseOrder->id, 404, 'Item does not belong to this purchase order');

        if ($purchaseOrder->status === 'received') {
            abort(422, 'Cannot modify a received purchase order');
        }

        DB::transaction(function () use ($purchaseOrder, $item) {
            $item->delete();
            $this->recalculateTotal($purchaseOrder);
        });

        return $this->respondDeleted('Line item removed');
    }

    /**
     * Receive the PO: add every line item's quantity to its ingredient's
     * stock and mark the order received. Idempotent — a PO can only be
     * received once.
     */
    public function receive(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status === 'received') {
            abort(422, 'Purchase order has already been received');
        }

        DB::transaction(function () use ($purchaseOrder) {
            $po = PurchaseOrder::with('items')->lockForUpdate()->findOrFail($purchaseOrder->id);

            foreach ($po->items as $item) {
                $ingredient = Ingredient::lockForUpdate()->find($item->ingredient_id);
                if ($ingredient) {
                    $ingredient->update(['stock' => (float) $ingredient->stock + (float) $item->quantity]);
                }
            }

            $po->update(['status' => 'received']);
        });

        return $this->respond(
            new PurchaseOrderResource($purchaseOrder->fresh(['supplier', 'items.ingredient'])),
            'Purchase order received; stock updated'
        );
    }

    private function recalculateTotal(PurchaseOrder $purchaseOrder): void
    {
        $total = $purchaseOrder->items()->sum(DB::raw('quantity * unit_cost'));
        $purchaseOrder->update(['total_cost' => round((float) $total, 2)]);
    }
}
