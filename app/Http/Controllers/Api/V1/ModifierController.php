<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\ModifierRequest;
use App\Http\Resources\MenuItemModifierResource;
use App\Models\MenuItem;
use App\Models\MenuItemModifier;
use Illuminate\Http\JsonResponse;

class ModifierController extends ApiController
{
    public function store(ModifierRequest $request, MenuItem $menuItem): JsonResponse
    {
        $modifier = $menuItem->modifiers()->create($request->validated());

        return $this->respondCreated(new MenuItemModifierResource($modifier), 'Modifier added');
    }

    public function update(ModifierRequest $request, MenuItemModifier $modifier): JsonResponse
    {
        $modifier->update($request->validated());

        return $this->respond(new MenuItemModifierResource($modifier->fresh()), 'Modifier updated');
    }

    public function destroy(MenuItemModifier $modifier): JsonResponse
    {
        $modifier->delete();

        return $this->respondDeleted('Modifier removed');
    }
}
