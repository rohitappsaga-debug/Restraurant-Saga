<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AddOrderItemsRequest;
use App\Http\Requests\Api\ApplyDiscountRequest;
use App\Http\Requests\Api\CancelOrderRequest;
use App\Http\Requests\Api\PayOrderRequest;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\BillingService;
use App\Services\KOTService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    public function __construct(
        protected OrderService $orderService,
        protected KOTService $kotService,
        protected BillingService $billingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['orderItems.menuItem', 'tables', 'creator'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('open'), fn ($q) => $q->open())
            ->when($request->filled('is_paid'), fn ($q) => $q->where('is_paid', $request->boolean('is_paid')))
            ->when($request->query('table_number'), fn ($q, $n) => $q->where('table_number', $n))
            ->when($request->query('date_from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('date_to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(min((int) $request->query('limit', 15), 100));

        return $this->respond(OrderResource::collection($orders));
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['orderItems.menuItem', 'orderItems.kot', 'tables', 'kots.items', 'creator', 'paymentTransactions']);

        return $this->respond([
            'order' => new OrderResource($order),
            'totals' => $this->billingService->calculateOrderTotals($order),
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder(
            $request->validated('table_ids'),
            $request->user()->id
        );

        if ($items = $request->validated('items')) {
            $order = $this->orderService->addItems($order, $items, $this->kotService);
        } else {
            $order->load(['orderItems.menuItem', 'tables']);
        }

        return $this->respondCreated(new OrderResource($order), 'Order created successfully');
    }

    public function addItems(AddOrderItemsRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->addItems($order, $request->validated('items'), $this->kotService);

        return $this->respond(new OrderResource($order), 'Items sent to kitchen');
    }

    public function serveItem(Request $request, Order $order, OrderItem $item): JsonResponse
    {
        abort_unless($item->order_id === $order->id, 404, 'Item does not belong to this order');

        $item = $this->orderService->serveItem($item, $request->user()->id);

        return $this->respond(new \App\Http\Resources\OrderItemResource($item), 'Item marked as served');
    }

    public function serveAll(Request $request, Order $order): JsonResponse
    {
        $order = $this->orderService->serveAll($order, $request->user()->id);

        return $this->respond(new OrderResource($order), 'All ready items marked as served');
    }

    public function bill(Order $order): JsonResponse
    {
        $order->load(['orderItems.menuItem', 'tables', 'paymentTransactions']);

        return $this->respond([
            'order' => new OrderResource($order),
            'totals' => $this->billingService->calculateOrderTotals($order),
            'currency' => \App\Models\Setting::current()?->currency ?? '₹',
        ]);
    }

    public function applyDiscount(ApplyDiscountRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->applyDiscount(
            $order,
            $request->validated('type'),
            (float) $request->validated('value'),
            $this->billingService
        );

        return $this->respond([
            'order' => new OrderResource($order),
            'totals' => $this->billingService->calculateOrderTotals($order),
        ], 'Discount applied');
    }

    public function pay(PayOrderRequest $request, Order $order): JsonResponse
    {
        $result = $this->orderService->payOrder(
            $order,
            $request->validated('method'),
            $this->billingService,
            $request->validated('discount_type'),
            (float) ($request->validated('discount_value') ?? 0)
        );

        return $this->respond([
            'order' => new OrderResource($result['order']),
            'totals' => $result['totals'],
            'paid' => $result['paid'],
        ], 'Payment recorded. Tables released for cleaning.');
    }

    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->cancelOrder(
            $order,
            $request->validated('reason'),
            $request->user()->id
        );

        return $this->respond(new OrderResource($order), 'Order cancelled');
    }

    public function toggleHold(Order $order): JsonResponse
    {
        $order = $this->orderService->toggleHold($order);

        return $this->respond(
            new OrderResource($order),
            $order->hold_status ? 'Order placed on hold' : 'Order resumed'
        );
    }
}
