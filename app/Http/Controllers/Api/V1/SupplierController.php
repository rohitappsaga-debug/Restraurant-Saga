<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $suppliers = Supplier::query()
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
            ->orderBy('name')
            ->paginate(min((int) $request->query('limit', 20), 100));

        return $this->respond(SupplierResource::collection($suppliers));
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return $this->respond(new SupplierResource($supplier));
    }

    public function store(SupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create($request->validated());

        return $this->respondCreated(new SupplierResource($supplier), 'Supplier created');
    }

    public function update(SupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier->update($request->validated());

        return $this->respond(new SupplierResource($supplier->fresh()), 'Supplier updated');
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return $this->respondDeleted('Supplier deleted');
    }
}
