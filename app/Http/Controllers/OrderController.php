<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService) {
        $this->orderService = $orderService;
    }

    public function index(Request $request): JsonResponse {
        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $filters = $request->only(['status', 'tableNumber', 'dateFrom', 'dateTo']);
        
        $orders = $this->orderService->getOrders($page, $limit, $filters);
        
        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'pagination' => [
                'page' => $orders->currentPage(),
                'limit' => $orders->perPage(),
                'total' => $orders->total(),
                'totalPages' => $orders->lastPage()
            ]
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse {
        $order = $this->orderService->createOrder(
            $request->validated(), 
            $request->user()->id
        );
        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order created successfully'
        ], 201);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, string $id): JsonResponse {
        $order = $this->orderService->updateOrderStatus($id, $request->status);
        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order status updated successfully'
        ]);
    }
}
