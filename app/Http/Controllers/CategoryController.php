<?php
namespace App\Http\Controllers;
use App\Http\Requests\CategoryRequest;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller {
    public function __construct(protected CategoryService $service) {}

    public function index(Request $request): JsonResponse 
    { 
        $limit = $request->query('limit', 15);
        $categories = $this->service->getAllPaginated($limit);
        return response()->json([
            'success' => true,
            'data' => $categories->items(),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage()
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

    public function store(CategoryRequest $request): JsonResponse 
    { 
        return response()->json([
            'success' => true,
            'data' => $this->service->create($request->validated())
        ], 201); 
    }

    public function update(CategoryRequest $request, string $id): JsonResponse 
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
            'message' => 'Category deleted successfully'
        ], 200); 
    }
}