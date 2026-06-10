<?php

namespace App\Livewire\Admin;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class Suppliers extends Component
{
    use WithPagination;

    public $showAddDialog = false;
    public $editingSupplierId = null;

    // Form fields
    public $name = '';
    public $contact_name = '';
    public $email = '';
    public $phone = '';
    public $address = '';

    public $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->showAddForm = true; // In the view we'll use a toggle or dialog
        $this->showAddDialog = true;
    }

    public function openEdit($id)
    {
        $supplier = Supplier::findOrFail($id);
        $this->editingSupplierId = $id;
        $this->name = $supplier->name;
        $this->contact_name = $supplier->contact_name;
        $this->email = $supplier->email;
        $this->phone = $supplier->phone;
        $this->address = $supplier->address;
        $this->showAddDialog = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $data = [
            'name' => $this->name,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
        ];

        if ($this->editingSupplierId) {
            Supplier::findOrFail($this->editingSupplierId)->update($data);
            $this->dispatch('notify', ['message' => 'Supplier updated successfully', 'type' => 'success']);
        } else {
            Supplier::create($data);
            $this->dispatch('notify', ['message' => 'Supplier added successfully', 'type' => 'success']);
        }

        $this->resetForm();
    }

    public function delete($id)
    {
        Supplier::findOrFail($id)->delete();
        $this->dispatch('notify', ['message' => 'Supplier removed', 'type' => 'success']);
    }

    public function resetForm()
    {
        $this->editingSupplierId = null;
        $this->name = '';
        $this->contact_name = '';
        $this->email = '';
        $this->phone = '';
        $this->address = '';
        $this->showAddDialog = false;
    }

    public function getSuppliersProperty()
    {
        return Supplier::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('contact_name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->withCount('purchaseOrders')
            ->latest()
            ->paginate(12);
    }

    public function render()
    {
        return view('livewire.admin.suppliers', [
            'suppliers' => $this->suppliers,
        ])->layout('layouts.admin');
    }
}
