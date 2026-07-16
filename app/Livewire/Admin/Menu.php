<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\MenuItemModifier;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

use Livewire\WithFileUploads;

class Menu extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $filterStatus = 'all';
    public $filterType = 'all';
    public $selectedCategory = 'All';

    public $showAddDialog = false;
    public $editingItemId = null;
    public $generatingIds = []; // Track items being processed
    public $generateOnSave = false; // Flag for triggering AI after save
    public $generationStatus = ''; // Track human-readable status

    protected function getListeners()
    {
        return [
            "echo:menu-updates,.menu.item.updated" => "notifyImageReady",
        ];
    }

    // Form fields
    public $name = '';
    public $categoryId = '';
    public $price = 0;
    public $description = '';
    public $available = true;
    public $preparation_time = 15;
    public $is_veg = true;
    public $available_from = '';
    public $available_to = '';
    public $availability_reason = '';
    public $image;
    public $currentImage;

    // Modifier form
    public $newModifierName = '';
    public $newModifierPrice = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
        'filterType' => ['except' => 'all'],
        'selectedCategory' => ['except' => 'All'],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterStatus', 'filterType', 'selectedCategory']);
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->editingItemId = null;
        $this->name = '';
        $this->categoryId = Category::first()?->id ?? '';
        $this->price = 0;
        $this->description = '';
        $this->available = true;
        $this->preparation_time = 15;
        $this->is_veg = true;
        $this->available_from = '';
        $this->available_to = '';
        $this->availability_reason = '';
        $this->image = null;
        $this->currentImage = null;
        $this->showAddDialog = false;
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->showAddDialog = true;
    }

    public function openEdit($id)
    {
        $item = MenuItem::with('modifiers')->findOrFail($id);
        $this->editingItemId = $id;
        $this->name = $item->name;
        $this->categoryId = $item->category_id;
        $this->price = $item->price;
        $this->description = $item->description;
        $this->available = $item->available;
        $this->preparation_time = $item->preparation_time;
        $this->is_veg = $item->is_veg;
        $this->available_from = $item->available_from;
        $this->available_to = $item->available_to;
        $this->availability_reason = $item->availability_reason;
        $this->currentImage = $item->image;
        $this->showAddDialog = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'categoryId' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'available' => 'boolean',
            'preparation_time' => 'required|integer|min:1',
            'is_veg' => 'boolean',
            'available_from' => 'nullable|string',
            'available_to' => 'nullable|string',
            'availability_reason' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = $this->currentImage;

        if ($this->image) {
            // Delete old image if exists
            if ($this->currentImage && \Storage::disk('public')->exists($this->currentImage)) {
                \Storage::disk('public')->delete($this->currentImage);
            }
            $imagePath = $this->image->store('dishes', 'public');
            \App\Jobs\ProcessImageOptimization::dispatch($imagePath);
        }

        $categoryName = Category::find($this->categoryId)?->name ?? 'Uncategorized';

        $data = [
            'name' => $this->name,
            'category' => $categoryName,
            'category_id' => $this->categoryId,
            'price' => $this->price,
            'description' => $this->description,
            'available' => $this->available,
            'preparation_time' => $this->preparation_time,
            'is_veg' => $this->is_veg,
            'available_from' => $this->available_from ?: null,
            'available_to' => $this->available_to ?: null,
            'availability_reason' => $this->availability_reason ?: null,
            'image' => $imagePath,
        ];

        if ($this->editingItemId) {
            $item = MenuItem::findOrFail($this->editingItemId);
            $item->update($data);
            $this->dispatch('notify', ['message' => 'Item updated successfully', 'type' => 'success']);
        } else {
            $item = MenuItem::create($data);
            $this->dispatch('notify', ['message' => 'Item created successfully', 'type' => 'success']);
        }

        \Illuminate\Support\Facades\Cache::forget('admin.menu.stats');

        if ($this->generateOnSave && isset($item)) {
            $this->generateAIImage($item->id);
            $this->generateOnSave = false;
        }

        $this->resetForm();
    }

    public function confirmAIImageGeneration($id)
    {
        $item = MenuItem::findOrFail($id);
        
        if ($item->image && \Storage::disk('public')->exists($item->image)) {
            $this->dispatch('confirm-ai-generation', id: $id);
            return;
        }

        $this->generateAIImage($id);
    }

    public function generateAIImage($id)
    {
        $item = MenuItem::findOrFail($id);
        
        // Add to generating list
        if (!in_array($id, $this->generatingIds)) {
            $this->generatingIds[] = $id;
        }
        
        \App\Jobs\GenerateMenuItemImage::dispatch($item);
        
        $this->generationStatus = 'AI generation started in background...';
    }

    public function notifyImageReady($data)
    {
        $itemId = $data['menuItem']['id'] ?? null;
        if ($itemId && in_array($itemId, $this->generatingIds)) {
            $this->generatingIds = array_diff($this->generatingIds, [$itemId]);
            
            if (empty($this->generatingIds)) {
                $this->generationStatus = 'Image generated successfully!';
            }
            
            $this->dispatch('notify', ['message' => 'Image ready for ' . ($data['menuItem']['name'] ?? 'item'), 'type' => 'success']);
        }
    }


    public function delete($id)
    {
        $item = MenuItem::findOrFail($id);
        
        if ($item->image && \Storage::disk('public')->exists($item->image)) {
            \Storage::disk('public')->delete($item->image);
        }

        $item->delete();
        \Illuminate\Support\Facades\Cache::forget('admin.menu.stats');
        $this->resetPage();
        $this->dispatch('notify', ['message' => 'Item deleted successfully', 'type' => 'success']);
    }

    public function addModifier()
    {
        if (!$this->editingItemId || empty($this->newModifierName)) return;

        $this->validate([
            'newModifierName' => 'required|string|max:255',
            'newModifierPrice' => 'required|numeric|min:0',
        ]);

        MenuItemModifier::create([
            'menu_item_id' => $this->editingItemId,
            'name' => $this->newModifierName,
            'price' => $this->newModifierPrice,
            'is_available' => true,
        ]);

        $this->newModifierName = '';
        $this->newModifierPrice = 0;
        $this->dispatch('notify', ['message' => 'Modifier added', 'type' => 'success']);
    }

    public function removeModifier($id)
    {
        MenuItemModifier::findOrFail($id)->delete();
        $this->dispatch('notify', ['message' => 'Modifier removed', 'type' => 'success']);
    }

    #[Computed]
    public function menuItems()
    {
        return MenuItem::query()
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($this->search) . '%'])
                      ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($this->search) . '%']);
                });
            })
            ->when($this->filterStatus !== 'all', function ($query) {
                $query->where('available', $this->filterStatus === 'available');
            })
            ->when($this->filterType !== 'all', function ($query) {
                $query->where('is_veg', $this->filterType === 'veg');
            })
            ->when($this->selectedCategory !== 'All', function ($query) {
                $query->where('category_id', $this->selectedCategory);
            })
            ->with(['categoryInfo', 'modifiers'])
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function stats()
    {
        return \Illuminate\Support\Facades\Cache::remember('admin.menu.stats', 60, function() {
            return [
                'total' => MenuItem::count(),
                'available' => MenuItem::where('available', true)->count(),
                'unavailable' => MenuItem::where('available', false)->count(),
                'categories' => Category::count(),
            ];
        });
    }

    public function render()
    {
        $categories = Category::all();

        $settings = \Illuminate\Support\Facades\Cache::rememberForever('settings', function() {
            return \App\Models\Setting::current()?->toArray() ?? ['currency' => '₹'];
        });

        return view('livewire.admin.menu', [
            'items' => $this->menuItems,
            'categories' => $categories,
            'stats' => $this->stats,
            'settings' => $settings
        ])->layout('layouts.admin');
    }
}
