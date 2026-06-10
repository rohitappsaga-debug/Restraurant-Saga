<?php
namespace App\Http\Controllers;
use App\Http\Requests\TableRequest;
use App\Services\TableService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TableController extends Controller {
    public function __construct(protected TableService $service) {}

    public function index(Request $request): JsonResponse 
    { 
        $limit = $request->query('limit', 15);
        $tables = $this->service->getAllPaginated($limit);
        return response()->json([
            'success' => true,
            'data' => $tables->items(),
            'pagination' => [
                'current_page' => $tables->currentPage(),
                'per_page' => $tables->perPage(),
                'total' => $tables->total(),
                'last_page' => $tables->lastPage()
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

    public function store(TableRequest $request): JsonResponse 
    { 
        return response()->json([
            'success' => true,
            'data' => $this->service->create($request->validated())
        ], 201); 
    }

    public function update(TableRequest $request, string $id): JsonResponse 
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
            'message' => 'Table deleted successfully'
        ], 200); 
    }
}