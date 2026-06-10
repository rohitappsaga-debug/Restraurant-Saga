<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Enums\OrderStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class Orders extends Component
{
    use WithPagination;

    public $search = '';
    public $activeTab = 'all';
    public $selectedOrderId = null;
    public $showDetailModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'activeTab' => ['except' => 'all'],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updateOrderStatus($orderId, $status)
    {
        $order = Order::findOrFail($orderId);
        $order->status = $status;
        $order->save();

        if ($this->selectedOrderId === $orderId) {
            $this->selectedOrderId = $orderId; // Refresh binding
        }

        $this->dispatch('notify', ['message' => "Order updated to " . strtoupper($status), 'type' => 'success']);
    }

    public function viewOrderDetails($id)
    {
        $this->selectedOrderId = $id;
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedOrderId = null;
    }

    public function getSelectedOrderProperty()
    {
        if (!$this->selectedOrderId) return null;
        return Order::with(['orderItems.menuItem', 'orderItems.modifiers', 'table', 'customer'])->find($this->selectedOrderId);
    }

    public function getActiveOrdersProperty()
    {
        return $this->baseQuery()
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::PREPARING, OrderStatus::READY])
            ->latest()
            ->get();
    }

    public function getPastOrdersProperty()
    {
        return $this->baseQuery()
            ->whereIn('status', [OrderStatus::SERVED, OrderStatus::DELIVERED, OrderStatus::CANCELLED])
            ->latest()
            ->paginate(10, ['*'], 'pastPage');
    }

    public function getFilteredOrdersProperty()
    {
        if ($this->activeTab === 'all') {
            return null; // Handle separately in blade
        }

        return $this->baseQuery()
            ->where('status', $this->activeTab)
            ->latest()
            ->paginate(12);
    }

    protected function baseQuery()
    {
        return Order::query()
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('table_number', 'like', '%' . $this->search . '%')
                      ->orWhere('id', 'like', '%' . $this->search . '%');
                });
            })
            ->with(['orderItems.menuItem', 'table']);
    }

    public function getStatusCountsProperty()
    {
        return Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status.value')
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.orders', [
            'statusCounts' => $this->statusCounts,
            'activeOrders' => $this->activeOrders,
            'pastOrders' => $this->pastOrders,
            'filteredOrders' => $this->filteredOrders,
        ])->layout('layouts.admin');
    }
}
