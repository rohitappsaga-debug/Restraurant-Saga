<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\IngredientRequest;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $ingredients = Ingredient::query()
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
            ->when($request->boolean('low_stock'), fn ($q) => $q->whereColumn('stock', '<=', 'min_level'))
            ->orderBy('name')
            ->paginate(min((int) $request->query('limit', 20), 100));

        return $this->respond(IngredientResource::collection($ingredients));
    }

    public function show(Ingredient $ingredient): JsonResponse
    {
        return $this->respond(new IngredientResource($ingredient));
    }

    public function store(IngredientRequest $request): JsonResponse
    {
        $ingredient = Ingredient::create($request->validated());

        return $this->respondCreated(new IngredientResource($ingredient), 'Ingredient created');
    }

    public function update(IngredientRequest $request, Ingredient $ingredient): JsonResponse
    {
        $ingredient->update($request->validated());

        return $this->respond(new IngredientResource($ingredient->fresh()), 'Ingredient updated');
    }

    /** Atomic stock adjustment (delta may be negative). */
    public function adjustStock(Request $request, Ingredient $ingredient): JsonResponse
    {
        $validated = $request->validate(['delta' => 'required|numeric']);

        DB::transaction(function () use ($ingredient, $validated) {
            $locked = Ingredient::lockForUpdate()->findOrFail($ingredient->id);
            $newStock = (float) $locked->stock + (float) $validated['delta'];

            if ($newStock < 0) {
                abort(422, 'Stock cannot go negative');
            }

            $locked->update(['stock' => $newStock]);
        });

        return $this->respond(new IngredientResource($ingredient->fresh()), 'Stock adjusted');
    }

    public function destroy(Ingredient $ingredient): JsonResponse
    {
        $ingredient->delete();

        return $this->respondDeleted('Ingredient deleted');
    }
}
