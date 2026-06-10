<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Enums\UserRole;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Users extends Component
{
    use WithPagination;

    public $showAddDialog = false;
    public $editingUserId = null;
    
    // Form fields
    public $name = '';
    public $email = '';
    public $password = '';
    public $role = 'waiter';
    public $active = true;

    // Filters
    public $search = '';
    public $statusFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
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
        $user = User::findOrFail($id);
        $this->editingUserId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->active = $user->active;
        $this->password = ''; // Clear password field
        $this->showAddDialog = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->editingUserId)],
            'role' => 'required|string',
            'active' => 'boolean',
        ];

        if (!$this->editingUserId) {
            $rules['password'] = 'required|min:6';
        } else {
            $rules['password'] = 'nullable|min:6';
        }

        $this->validate($rules);

        // Safety check for last admin
        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            if ($user->role === UserRole::ADMIN && $user->active) {
                if ($this->role !== 'admin' || !$this->active) {
                    $adminCount = User::where('role', UserRole::ADMIN)->where('active', true)->count();
                    if ($adminCount <= 1) {
                        $this->dispatch('notify', ['message' => 'Cannot deactivate or change role of the last active admin.', 'type' => 'error']);
                        return;
                    }
                }
            }
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'active' => $this->active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            User::findOrFail($this->editingUserId)->update($data);
            $this->dispatch('notify', ['message' => 'User updated successfully', 'type' => 'success']);
        } else {
            User::create($data);
            $this->dispatch('notify', ['message' => 'User created successfully', 'type' => 'success']);
        }

        $this->resetForm();
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->dispatch('notify', ['message' => 'You cannot delete yourself.', 'type' => 'error']);
            return;
        }

        if ($user->role === UserRole::ADMIN) {
            $adminCount = User::where('role', UserRole::ADMIN)->count();
            if ($adminCount <= 1) {
                $this->dispatch('notify', ['message' => 'Cannot delete the last admin.', 'type' => 'error']);
                return;
            }
        }

        $user->delete();
        $this->resetPage();
        $this->dispatch('notify', ['message' => 'User deleted successfully', 'type' => 'success']);
    }

    public function resetForm()
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'waiter';
        $this->active = true;
        $this->showAddDialog = false;
        $this->resetValidation();
    }

    public function getAvatarColor($name)
    {
        $colors = [
            '#6366f1', // Indigo
            '#8b5cf6', // Violet
            '#ec4899', // Pink
            '#f43f5e', // Rose
            '#f59e0b', // Amber
            '#10b981', // Emerald
            '#06b6d4', // Cyan
            '#3b82f6', // Blue
        ];
        $hash = crc32($name . 'pepper'); // Add salt
        return $colors[abs($hash) % count($colors)];
    }

    public function getUsersProperty()
    {
        return User::query()
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($this->search) . '%'])
                      ->orWhereRaw('LOWER(email) LIKE ?', ['%' . strtolower($this->search) . '%']);
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('active', $this->statusFilter === 'active');
            })
            ->latest()
            ->paginate(12);
    }

    public function getStatsProperty()
    {
        return [
            'total' => User::count(),
            'active' => User::where('active', true)->count(),
            'waiters' => User::where('role', UserRole::WAITER)->count(),
            'admins' => User::where('role', UserRole::ADMIN)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.users', [
            'users' => $this->users,
            'stats' => $this->stats,
        ])->layout('layouts.admin');
    }
}
