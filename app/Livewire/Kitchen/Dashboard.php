<?php

namespace App\Livewire\Kitchen;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\TableSession;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

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
            "echo:kitchen,KOTCreated" => 'onNewOrder',
        ];
    }

    public function onNewOrder($data)
    {
        $this->dispatch('play-alert');
        $this->dispatch('notify', message: "New Order for Table " . ($data['table_number'] ?? '#?'), type: 'info');
    }

    #[Layout('layouts.kitchen')]
    public function render()
    {
        // One large group card per table:
        // We fetch sessions that are active
        $sessions = TableSession::with(['table', 'orders.orderItems.menuItem', 'orders.orderItems.kot'])
            ->where('status', 'active')
            ->get();

        $menuItems = MenuItem::query()
            ->when($this->searchTerm, fn($q) => $q->where('name', 'ilike', '%' . $this->searchTerm . '%'))
            ->when($this->selectedMenuCategory !== 'All', fn($q) => $q->where('category', $this->selectedMenuCategory))
            ->get();

        $menuCategories = ['All', ...MenuItem::distinct()->pluck('category')->toArray()];

        $counts = [
            'all' => $sessions->count(),
            'pending' => $sessions->filter(fn($s) => $s->orders->flatMap(fn($o) => $o->orderItems)->contains(fn($i) => in_array($i->status->value, [OrderStatus::PENDING->value, OrderStatus::SENT->value])))->count(),
            'preparing' => $sessions->filter(fn($s) => $s->orders->flatMap(fn($o) => $o->orderItems)->contains('status', OrderStatus::PREPARING))->count(),
            'ready' => $sessions->filter(fn($s) => $s->orders->flatMap(fn($o) => $o->orderItems)->contains('status', OrderStatus::READY))->count(),
            'served' => $sessions->filter(fn($s) => $s->orders->flatMap(fn($o) => $o->orderItems)->contains('status', OrderStatus::SERVED))->count(),
        ];


        return view('livewire.kitchen.dashboard', [
            'sessions' => $sessions,
            'menuItems' => $menuItems,
            'menuCategories' => $menuCategories,
            'counts' => $counts
        ]);
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
