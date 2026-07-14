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

    public function markAsPaid($orderId, $method = 'cash', \App\Services\OrderService $orderService = null)
    {
        $orderService ??= new \App\Services\OrderService();

        $details = null;
        $order = null;

        DB::transaction(function () use ($orderId, $method, $orderService, &$details, &$order) {
            $order = Order::lockForUpdate()->findOrFail($orderId);

            $details = $this->calculateBillDetails($order);
            $alreadyPaid = (float) $order->paymentTransactions()->where('status', 'completed')->sum('amount');
            $remaining = max(0, round($details['total'] - $alreadyPaid, 2));

            $order->update(['total' => $details['total']]);

            // Single payment path: records the transaction and frees the order's tables
            $orderService->settleOrder($order, $method, $remaining);
        });

        // Broadcast payment for real-time notifications
        event(new \App\Events\PaymentReceived($order, $details['total'], $method));

        $this->dispatch('notify', ['message' => 'Payment recorded successfully', 'type' => 'success']);
    }

    public function clearAllPending()
    {
        $count = 0;

        DB::transaction(function () use (&$count) {
            $orderIds = Order::where('is_paid', false)
                ->where('status', '!=', OrderStatus::CANCELLED)
                ->pluck('id');

            $count = $orderIds->count();

            Order::whereIn('id', $orderIds)->update([
                'is_paid' => true,
                'payment_method' => \App\Enums\PaymentMethod::CASH,
                'status' => OrderStatus::SERVED
            ]);

            \App\Models\Table::whereIn('current_order_id', $orderIds)->update([
                'status' => \App\Enums\TableStatus::CLEANING,
                'current_order_id' => null,
            ]);
        });

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

        $pendingCount = Order::where('is_paid', false)
            ->where('status', '!=', OrderStatus::CANCELLED)
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
