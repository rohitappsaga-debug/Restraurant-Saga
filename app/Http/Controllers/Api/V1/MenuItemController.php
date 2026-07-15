<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\MenuAvailabilityUpdated;
use App\Http\Requests\MenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Jobs\GenerateMenuItemImage;
use App\Jobs\ProcessImageOptimization;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $items = MenuItem::with(['modifiers', 'categoryInfo'])
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
            ->when($request->filled('available'), fn ($q) => $q->where('available', $request->boolean('available')))
            ->orderBy('name')
            ->get();

        return $this->respond(MenuItemResource::collection($items));
    }

    public function show(MenuItem $menuItem): JsonResponse
    {
        $menuItem->load(['modifiers', 'categoryInfo']);

        return $this->respond(new MenuItemResource($menuItem));
    }

    public function store(MenuItemRequest $request): JsonResponse
    {
        $item = MenuItem::create($this->withLegacyCategoryName($request->validated()));

        return $this->respondCreated(new MenuItemResource($item->load(['modifiers', 'categoryInfo'])), 'Menu item created');
    }

    public function update(MenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        $menuItem->update($this->withLegacyCategoryName($request->validated()));

        return $this->respond(new MenuItemResource($menuItem->fresh(['modifiers', 'categoryInfo'])), 'Menu item updated');
    }

    /** The legacy NOT NULL `category` name column must track `category_id`. */
    private function withLegacyCategoryName(array $data): array
    {
        if (!empty($data['category_id'])) {
            $data['category'] = \App\Models\Category::find($data['category_id'])?->name ?? 'Uncategorized';
        }

        return $data;
    }

    public function destroy(MenuItem $menuItem): JsonResponse
    {
        $menuItem->delete();

        return $this->respondDeleted('Menu item deleted');
    }

    /** Toggle 86'd status — allowed for kitchen staff as well as managers. */
    public function toggleAvailability(MenuItem $menuItem): JsonResponse
    {
        $menuItem->update(['available' => !$menuItem->available]);

        MenuAvailabilityUpdated::dispatch($menuItem);

        return $this->respond(
            new MenuItemResource($menuItem->fresh(['modifiers', 'categoryInfo'])),
            $menuItem->available ? 'Item is now available' : 'Item is now unavailable'
        );
    }

    /** Upload/replace a dish photo (multipart). Old image is removed, new one optimized async. */
    public function uploadImage(Request $request, MenuItem $menuItem): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        if ($menuItem->image && Storage::disk('public')->exists($menuItem->image)) {
            Storage::disk('public')->delete($menuItem->image);
        }

        $path = $request->file('image')->store('dishes', 'public');
        $menuItem->update(['image' => $path]);

        ProcessImageOptimization::dispatch($path);

        return $this->respond(
            new MenuItemResource($menuItem->fresh(['modifiers', 'categoryInfo'])),
            'Image uploaded'
        );
    }

    /** Kick off background AI image generation for a dish (same job the web UI uses). */
    public function generateImage(MenuItem $menuItem): JsonResponse
    {
        GenerateMenuItemImage::dispatch($menuItem);

        return $this->respond(
            new MenuItemResource($menuItem->fresh(['modifiers', 'categoryInfo'])),
            'AI image generation started. It will appear once ready.'
        );
    }
}
