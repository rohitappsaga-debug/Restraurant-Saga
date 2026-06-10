<?php

namespace App\Livewire\Kitchen;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\TableSession;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

class Dashboard extends Component
{
    public $activeTab = 'all';
    public $view = 'orders'; // 'orders' or 'menu'
    public $searchTerm = '';
    public $selectedMenuCategory = 'All';

    protected $queryString = ['view', 'activeTab'];

    public function getListeners()
    {
        return [
            "echo:kitchen,.KOTCreated" => 'onNewOrder',
        ];
    }

    public function onNewOrder($data)
    {
        $this->dispatch('play-alert');
        $this->dispatch('notify', message: "New Order for Table " . ($data['table_number'] ?? '#?'), type: 'info');
    }

    #[Computed]
    public function sessions()
    {
        return TableSession::with(['table', 'orders.orderItems.menuItem', 'orders.orderItems.kot'])
            ->where('status', 'active')
            ->get();
    }

    #[Computed]
    public function menuItems()
    {
        return MenuItem::query()
            ->when($this->searchTerm, fn($q) => $q->where('name', 'ilike', '%' . $this->searchTerm . '%'))
            ->when($this->selectedMenuCategory !== 'All', fn($q) => $q->where('category', $this->selectedMenuCategory))
            ->get();
    }

    #[Computed]
    public function menuCategories()
    {
        return ['All', ...MenuItem::distinct()->pluck('category')->toArray()];
    }

    #[Computed]
    public function counts()
    {
        $sessions = $this->sessions;
        
        $counts = [
            'all' => $sessions->count(),
            'pending' => 0,
            'preparing' => 0,
            'ready' => 0,
            'served' => 0,
        ];

        foreach ($sessions as $session) {
            $hasPending = false;
            $hasPreparing = false;
            $hasReady = false;
            $hasServed = false;
            
            foreach ($session->orders as $order) {
                foreach ($order->orderItems as $item) {
                    $val = is_object($item->status) ? $item->status->value : $item->status;
                    if ($val === OrderStatus::PENDING->value || $val === 'sent') $hasPending = true;
                    elseif ($val === OrderStatus::PREPARING->value) $hasPreparing = true;
                    elseif ($val === OrderStatus::READY->value) $hasReady = true;
                    elseif ($val === OrderStatus::SERVED->value) $hasServed = true;
                }
            }
            
            if ($hasPending) $counts['pending']++;
            if ($hasPreparing) $counts['preparing']++;
            if ($hasReady) $counts['ready']++;
            if ($hasServed) $counts['served']++;
        }

        return $counts;
    }

    #[Layout('layouts.kitchen')]
    public function render()
    {
        return view('livewire.kitchen.dashboard');
    }

    #[On('theme-persisted')]
    public function syncTheme($theme)
    {
        $user = Auth::user();
        if ($user) {
            $user->update(['theme' => $theme]);
        }
    }

    public function toggleTheme()
    {
        // Handled via Alpine store now, but kept for compatibility
        $user = Auth::user();
        $newTheme = $user->theme === 'light' ? 'dark' : 'light';
        $user->update(['theme' => $newTheme]);
        
        $this->dispatch('theme-updated', theme: $newTheme);
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function setView($view)
    {
        $this->view = $view;
    }

    public function updateItemStatus($orderId, $itemId, $status)
    {
        $item = OrderItem::find($itemId);
        if ($item) {
            $item->update(['status' => $status]);
            
            // Dispatch real-time notification event
            \App\Events\ItemStatusUpdated::dispatch($item);

            // Create a persistent notification for the waiter if item is READY
            if ($status === 'ready') {
                \App\Models\Notification::create([
                    'type' => \App\Enums\NotificationType::ALERT,
                    'message' => "Order Ready: {$item->menuItem->name} for Table {$item->order->table->number}",
                    'user_id' => $item->order->created_by, // Notify the specific waiter who placed the order
                    'read' => false
                ]);
            }
            
            // Sync order status based on items using the centralized service
            (new \App\Services\OrderService())->syncOrderStatus($item->order);
            
            $this->dispatch('notify', message: "Item updated to " . strtoupper($status), type: 'success');
        }
    }

    public function dismissOrder($orderId)
    {
        $order = Order::find($orderId);
        if ($order) {
            // Depending on flow, we might mark it 'served' or just 'delivered'
            $order->update(['status' => OrderStatus::SERVED]);
            $this->dispatch('notify', message: "Order #" . substr($orderId, -6) . " dismissed.", type: 'info');
        }
    }

    public function forceCloseSession($sessionId)
    {
        $session = TableSession::find($sessionId);
        if ($session) {
            $session->update(['status' => 'closed']);
            $this->dispatch('notify', message: "Table #" . $session->table->number . " cleared from kitchen display.", type: 'warning');
        }
    }

    public function toggleAvailability($itemId)
    {
        $item = MenuItem::find($itemId);
        if ($item) {
            $item->available = !$item->available;
            $item->save();
            
            \App\Events\MenuAvailabilityUpdated::dispatch($item);
            
            $status = $item->available ? 'Available' : 'Unavailable';
            $this->dispatch('notify', message: "{$item->name} is now {$status}", type: $item->available ? 'success' : 'warning');
        }
    }

}
