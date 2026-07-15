<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\RecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $recipes = Recipe::with(['menuItem', 'ingredient'])
            ->when($request->query('menu_item_id'), fn ($q, $id) => $q->where('menu_item_id', $id))
            ->paginate(min((int) $request->query('limit', 20), 100));

        return $this->respond(RecipeResource::collection($recipes));
    }

    public function show(Recipe $recipe): JsonResponse
    {
        return $this->respond(new RecipeResource($recipe->load(['menuItem', 'ingredient'])));
    }

    public function store(RecipeRequest $request): JsonResponse
    {
        $recipe = Recipe::create($request->validated());

        return $this->respondCreated(new RecipeResource($recipe->load(['menuItem', 'ingredient'])), 'Recipe created');
    }

    public function update(RecipeRequest $request, Recipe $recipe): JsonResponse
    {
        $recipe->update($request->validated());

        return $this->respond(new RecipeResource($recipe->fresh(['menuItem', 'ingredient'])), 'Recipe updated');
    }

    public function destroy(Recipe $recipe): JsonResponse
    {
        $recipe->delete();

        return $this->respondDeleted('Recipe deleted');
    }
}
