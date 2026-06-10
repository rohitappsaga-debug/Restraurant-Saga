<?php
namespace App\Http\Controllers;
use App\Http\Requests\MenuItemRequest;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller {
    public function __construct(protected MenuService $service) {}

    public function index(Request $request): JsonResponse 
    { 
        $limit = $request->query('limit', 15);
        $menuItems = $this->service->getAllPaginated($limit);
        return response()->json([
            'success' => true,
            'data' => $menuItems->items(),
            'pagination' => [
                'current_page' => $menuItems->currentPage(),
                'per_page' => $menuItems->perPage(),
                'total' => $menuItems->total(),
                'last_page' => $menuItems->lastPage()
            ]
        ]);
    }

    public function show(string $id): JsonResponse 
    { 
        return response()->json([
            'success' => true,
            'data' => $this->service->find($id)
        ]); 
    }

    public function store(MenuItemRequest $request): JsonResponse 
    { 
        return response()->json([
            'success' => true,
            'data' => $this->service->create($request->validated())
        ], 201); 
    }

    public function update(MenuItemRequest $request, string $id): JsonResponse 
    { 
        return response()->json([
            'success' => true,
            'data' => $this->service->update($id, $request->validated())
        ]); 
    }

    public function destroy(string $id): JsonResponse 
    { 
        $this->service->delete($id); 
        return response()->json([
            'success' => true,
            'message' => 'Menu item deleted successfully'
        ], 200); 
    }
}