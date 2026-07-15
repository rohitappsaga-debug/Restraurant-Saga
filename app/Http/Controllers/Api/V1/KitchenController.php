<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Requests\Api\UpdateKitchenItemStatusRequest;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\KOTService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KitchenController extends ApiController
{
    public function __construct(
        protected KOTService $kotService,
        protected OrderService $orderService,
    ) {}

    /**
     * Live kitchen queue: every open order that has items, oldest first —
     * the same query the kitchen dashboard renders.
     */
    public function queue(Request $request): JsonResponse
    {
        $orders = Order::open()
            ->whereHas('orderItems')
            ->with(['tables', 'orderItems.menuItem', 'orderItems.kot'])
            ->when($request->query('item_status'), function ($q, $status) {
                $q->whereHas('orderItems', fn ($iq) => $iq->where('status', $status));
            })
            ->orderBy('created_at')
            ->get();

        return $this->respond(OrderResource::collection($orders));
    }

    public function updateItemStatus(UpdateKitchenItemStatusRequest $request, OrderItem $item): JsonResponse
    {
        $item = $this->kotService->updateItemStatus($item, $request->validated('status'));

        return $this->respond(new OrderItemResource($item), 'Item updated to ' . strtoupper($request->validated('status')));
    }

    /** Clear an order from the kitchen display without touching the bill. */
    public function dismiss(Order $order): JsonResponse
    {
        $order->update(['status' => OrderStatus::SERVED]);

        return $this->respond(new OrderResource($order->fresh(['orderItems.menuItem', 'tables'])), 'Order dismissed from kitchen');
    }

    /** Force every remaining item to served and close the order on the board. */
    public function forceClose(Order $order): JsonResponse
    {
        $order = $this->orderService->forceClose($order);

        return $this->respond(new OrderResource($order), 'Order force-closed');
    }
}
