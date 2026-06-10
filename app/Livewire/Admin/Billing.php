<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Setting;
use App\Enums\OrderStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class Billing extends Component
{
    use WithPagination;

    public $search = '';
    public $dateFilter = 'all';
    public $selectedOrderId = null;
    public $showBillDialog = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFilter' => ['except' => 'all'],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedDateFilter()
    {
        $this->resetPage();
    }

    public function markAsPaid($orderId, $method = 'cash')
    {
        $order = Order::findOrFail($orderId);
        
        // Calculate the final total before saving if not already set or for accuracy
        $details = $this->calculateBillDetails($order);
        
        // Update the current order
        $order->update([
            'is_paid' => true,
            'payment_method' => $method,
            'status' => OrderStatus::SERVED,
            'total' => $details['total']
        ]);

        // If this order belongs to a session, update all other orders in that session too
        if ($order->session_id) {
            Order::where('session_id', $order->session_id)
                ->where('is_paid', false)
                ->update([
                    'is_paid' => true,
                    'payment_method' => $method,
                    'status' => OrderStatus::SERVED
                ]);
        }

        // Broadcast payment for real-time notifications
        event(new \App\Events\PaymentReceived($order, $details['total'], $method));

        $this->dispatch('notify', ['message' => 'Payment recorded successfully', 'type' => 'success']);
    }

    public function clearAllPending()
    {
        $count = Order::where('is_paid', false)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->count();

        Order::where('is_paid', false)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->update([
                'is_paid' => true,
                'payment_method' => 'CASH',
                'status' => OrderStatus::SERVED
            ]);

        $this->dispatch('notify', ['message' => "Successfully cleared {$count} pending bills", 'type' => 'success']);
    }

    public function viewBill($orderId)
    {
        $this->selectedOrderId = $orderId;
        $this->showBillDialog = true;
    }

    public function calculateBillDetails($order)
    {
        if (!$order) return null;

        // Sum up quantities * menu item prices
        $subtotal = $order->orderItems->sum(function($item) {
            return $item->quantity * ($item->menuItem->price ?? 0);
        });

        $settings = Setting::first();
        $taxEnabled = $settings?->tax_enabled ?? true;
        $taxRate = (float)($settings?->tax_rate ?? 5);
        
        $discountAmount = 0;
        if ($order->discount_type === 'percentage') {
            $discountAmount = ($subtotal * $order->discount_value) / 100;
        } else {
            $discountAmount = (float)$order->discount_value;
        }

        $taxableAmount = max(0, $subtotal - $discountAmount);
        $taxAmount = $taxEnabled ? ($taxableAmount * $taxRate) / 100 : 0;
        $total = $taxableAmount + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'tax' => $taxAmount,
            'total' => $total,
            'taxRate' => $taxRate
        ];
    }

    public function getOrdersProperty()
    {
        return Order::query()
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('id', 'like', '%' . $this->search . '%')
                      ->orWhere('table_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->dateFilter !== 'all', function ($query) {
                if ($this->dateFilter === 'today') {
                    $query->whereDate('created_at', today());
                } elseif ($this->dateFilter === '7days') {
                    $query->where('created_at', '>=', now()->subDays(7));
                } elseif ($this->dateFilter === '30days') {
                    $query->where('created_at', '>=', now()->subDays(30));
                }
            })
            ->with(['orderItems.menuItem', 'table'])
            ->latest()
            ->paginate(10);
    }

    public function getPendingOrdersProperty()
    {
        return Order::query()
            ->where('is_paid', false)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->whereHas('session', function($query) {
                $query->where('status', 'active');
            })
            ->when($this->search, function($q) {
                $q->where('table_number', 'like', '%' . $this->search . '%');
            })
            ->with(['orderItems.menuItem'])
            ->latest()
            ->get();
    }

    public function getStatsProperty()
    {
        $todayPaid = Order::whereDate('created_at', today())
            ->where('is_paid', true)
            ->sum('total');

        // Only count pending orders that have an active session (to match visible list)
        $pendingCount = Order::where('is_paid', false)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->whereHas('session', function($query) {
                $query->where('status', 'active');
            })
            ->count();

        return [
            'total_bills' => Order::count(),
            'pending_count' => $pendingCount,
            'paid_today' => (float)$todayPaid
        ];
    }

    public function render()
    {
        $globalSettings = Setting::first();
        $settingsData = [
            'currency' => $globalSettings?->currency ?? '₹',
            'taxRate' => (float) ($globalSettings?->tax_rate ?? 5),
            'taxEnabled' => $globalSettings?->tax_enabled ?? true,
        ];

        $selectedOrder = $this->selectedOrderId ? Order::with(['orderItems.menuItem', 'table'])->find($this->selectedOrderId) : null;

        return view('livewire.admin.billing', [
            'orders' => $this->orders,
            'pendingOrders' => $this->pendingOrders,
            'stats' => $this->stats,
            'settings' => $settingsData,
            'selectedOrder' => $selectedOrder,
            'selectedBillDetails' => $selectedOrder ? $this->calculateBillDetails($selectedOrder) : null,
        ])->layout('layouts.admin');
    }
}
