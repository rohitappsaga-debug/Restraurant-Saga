<?php

namespace App\Livewire\Admin;

use App\Models\Ingredient;
use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class Inventory extends Component
{
    use WithPagination;

    public $showAddDialog = false;
    public $showAdjustDialog = false;
    public $editingIngredientId = null;
    public $selectedIngredient = null;

    // Form fields
    public $name = '';
    public $unit = 'kg';
    public $stock = 0;
    public $min_level = 0;

    // Adjust fields
    public $adjustAmount = 0;
    public $adjustType = 'restock'; // restock, wastage, correction
    public $adjustReason = '';

    public $search = '';
    public $filterLowStock = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterLowStock' => ['except' => false],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->showAddDialog = true;
    }

    public function openEdit($id)
    {
        $ing = Ingredient::findOrFail($id);
        $this->editingIngredientId = $id;
        $this->name = $ing->name;
        $this->unit = $ing->unit;
        $this->stock = (float) $ing->stock;
        $this->min_level = (float) $ing->min_level;
        $this->showAddDialog = true;
    }

    public function openAdjust($id)
    {
        $this->selectedIngredient = Ingredient::findOrFail($id);
        $this->adjustAmount = 0;
        $this->adjustType = 'restock';
        $this->adjustReason = '';
        $this->showAdjustDialog = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:20',
            'stock' => 'required|numeric|min:0',
            'min_level' => 'required|numeric|min:0',
        ]);

        $data = [
            'name' => $this->name,
            'unit' => $this->unit,
            'stock' => $this->stock,
            'min_level' => $this->min_level,
        ];

        if ($this->editingIngredientId) {
            Ingredient::findOrFail($this->editingIngredientId)->update($data);
            $this->logActivity('Updated ingredient: ' . $this->name);
            $this->dispatch('notify', ['message' => 'Ingredient updated', 'type' => 'success']);
        } else {
            Ingredient::create($data);
            $this->logActivity('Created ingredient: ' . $this->name);
            $this->dispatch('notify', ['message' => 'Ingredient added', 'type' => 'success']);
        }

        $this->resetForm();
    }

    public function adjustStock()
    {
        if (!$this->selectedIngredient) return;

        $this->validate([
            'adjustAmount' => 'required|numeric|min:0.01',
            'adjustType' => 'required|string',
            'adjustReason' => 'nullable|string',
        ]);

        $ing = Ingredient::findOrFail($this->selectedIngredient->id);
        $oldStock = (float) $ing->stock;
        
        if ($this->adjustType === 'restock' || $this->adjustType === 'correction_plus') {
            $ing->stock += $this->adjustAmount;
        } else {
            $ing->stock -= $this->adjustAmount;
        }

        $ing->save();

        $this->logActivity("Stock adjustment for {$ing->name}: {$this->adjustType} ({$this->adjustAmount} {$ing->unit}). Reason: {$this->adjustReason}");
        
        $this->showAdjustDialog = false;
        $this->selectedIngredient = null;
        $this->dispatch('notify', ['message' => 'Stock adjusted successfully', 'type' => 'success']);
    }

    public function delete($id)
    {
        $ing = Ingredient::findOrFail($id);
        $name = $ing->name;
        $ing->delete();
        $this->logActivity('Deleted ingredient: ' . $name);
        $this->dispatch('notify', ['message' => 'Ingredient removed', 'type' => 'success']);
    }

    public function resetForm()
    {
        $this->editingIngredientId = null;
        $this->name = '';
        $this->unit = 'kg';
        $this->stock = 0;
        $this->min_level = 0;
        $this->showAddDialog = false;
    }

    private function logActivity($details)
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'Inventory Update',
            'details' => $details
        ]);
    }

    public function getIngredientsProperty()
    {
        return Ingredient::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterLowStock, function ($query) {
                $query->whereRaw('stock <= min_level');
            })
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.inventory', [
            'ingredients' => $this->ingredients,
            'stats' => [
                'total' => Ingredient::count(),
                'low' => Ingredient::whereRaw('stock <= min_level')->count(),
                'units' => Ingredient::distinct()->count('unit'),
                'healthy' => Ingredient::whereRaw('stock > min_level')->count(),
            ]
        ])->layout('layouts.admin');
    }
}
