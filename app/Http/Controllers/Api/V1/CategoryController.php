<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return $this->respond(CategoryResource::collection($categories));
    }

    public function show(Category $category): JsonResponse
    {
        return $this->respond(new CategoryResource($category));
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return $this->respondCreated(new CategoryResource($category), 'Category created');
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return $this->respond(new CategoryResource($category->fresh()), 'Category updated');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return $this->respondDeleted('Category deleted');
    }
}
