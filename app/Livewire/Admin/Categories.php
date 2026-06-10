<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class Categories extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = 'all';
    public $showDialog = false;
    public $showBulkDialog = false;
    
    // Form fields
    public $editingCategoryId = null;
    public $name = '';
    public $description = '';
    public $is_active = true;
    public $bulkInput = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterStatus']);
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->editingCategoryId = null;
        $this->name = '';
        $this->description = '';
        $this->is_active = true;
        $this->showDialog = false;
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->showDialog = true;
    }

    public function openEdit($id)
    {
        $category = Category::findOrFail($id);
        $this->editingCategoryId = $id;
        $this->name = $category->name;
        $this->description = $category->description;
        $this->is_active = $category->is_active;
        $this->showDialog = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($this->editingCategoryId) {
            $category = Category::findOrFail($this->editingCategoryId);
            $category->update([
                'name' => $this->name,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            $this->dispatch('notify', ['message' => 'Category updated successfully', 'type' => 'success']);
        } else {
            Category::create([
                'name' => $this->name,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            $this->dispatch('notify', ['message' => 'Category created successfully', 'type' => 'success']);
        }

        $this->resetForm();
    }

    public function delete($id)
    {
        $category = Category::findOrFail($id);
        
        // check if has menu items
        if ($category->menuItems()->count() > 0) {
            $this->dispatch('notify', ['message' => 'Cannot delete category with associated menu items', 'type' => 'error']);
            return;
        }

        $category->delete();
        $this->resetPage();
        $this->dispatch('notify', ['message' => 'Category deleted successfully', 'type' => 'success']);
    }

    public function saveBulk()
    {
        if (empty(trim($this->bulkInput))) {
            $this->dispatch('notify', ['message' => 'Please enter at least one category name', 'type' => 'error']);
            return;
        }

        $names = preg_split('/[\n,]+/', $this->bulkInput);
        
        // Clean and deduplicate names
        $uniqueNames = collect($names)
            ->map(fn($name) => trim($name))
            ->filter()
            ->unique(fn($name) => strtolower($name));

        $count = 0;
        foreach ($uniqueNames as $name) {
            // Check if already exists in DB to prevent duplicates
            if (!Category::where('name', $name)->exists()) {
                Category::create([
                    'name' => $name,
                    'is_active' => true,
                ]);
                $count++;
            }
        }

        $this->showBulkDialog = false;
        $this->bulkInput = '';
        
        if ($count > 0) {
            $this->dispatch('notify', [
                'message' => "Successfully created $count categories.",
                'type' => 'success'
            ]);
        } else {
            $this->dispatch('notify', [
                'message' => "No new categories were added (they might already exist).",
                'type' => 'warning'
            ]);
        }
    }

    public function getCategoriesProperty()
    {
        return Category::query()
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($this->search) . '%'])
                      ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($this->search) . '%']);
                });
            })
            ->when($this->filterStatus !== 'all', function ($query) {
                $query->where('is_active', $this->filterStatus === 'active');
            })
            ->withCount('menuItems')
            ->latest()
            ->paginate(15);
    }

    public function getGradients($name)
    {
        $gradients = [
            'from-indigo-500 to-purple-600',
            'from-rose-500 to-pink-600',
            'from-amber-400 to-orange-600',
            'from-emerald-400 to-teal-600',
            'from-blue-500 to-cyan-600',
            'from-violet-500 to-fuchsia-600',
            'from-orange-400 to-red-600',
            'from-cyan-400 to-blue-600',
        ];

        // Use a consistent hash based on name
        $hash = crc32(strtolower($name));
        return $gradients[abs($hash) % count($gradients)];
    }

    public function render()
    {
        return view('livewire.admin.categories', [
            'categories' => $this->categories
        ])->layout('layouts.admin');
    }
}
