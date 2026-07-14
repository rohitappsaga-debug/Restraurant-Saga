<?php

namespace App\Livewire\Waiter;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Notification;
use App\Models\Table;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Enums\TableStatus;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Dashboard extends Component
{
    #[Url]
    public $view = 'home'; // home, alerts, profile, menu, summary, bill
    public $search = '';

    public function getListeners()
    {
        return [
            "echo:orders,ItemStatusUpdated" => 'onItemStatusUpdated',
            "echo:orders,TableStatusChanged" => '$refresh',
            "echo:menu-updates,.MenuAvailabilityUpdated" => '$refresh',
        ];
    }

    public function onItemStatusUpdated($payload)
    {
        if (Auth::user()->notifications_enabled) {
            $this->dispatch('play-alert');
        }
    }

    public function updatedView($value)
    {
        if ($value === 'alerts') {
            $this->markAllAsRead();
        }
    }
    
    // Order State
    public array $selectedTableIds = [];
    #[Url]
    public $selectedCategoryId = null;
    #[Url]
    public $menuSearch = '';
    #[Url]
    public $currentOrderId = null;
    
    // Cart: [uuid => ['item_id' => ..., 'name' => ..., 'price' => ..., 'quantity' => ..., 'modifiers' => [...], 'notes' => '']]
    public $cart = []; 

    // Modifiers State
    public $showModifiers = false;
    public $modifierItem = null;
    public $selectedModifiers = []; // [id1, id2]

    // Discount State
    public $discountType = 'percentage'; // percentage, amount
    public $discountValue = 0;
    public $showDiscountRow = false;

    // Payment State
    public $showPaymentModal = false;
    public $paymentMethod = 'cash'; // cash, card, upi

    // Tax Settings
    public $taxEnabled = true;
    public $taxPercent = 5;

    #[Computed]
    public function tables()
    {
        return Table::orderBy('number')
            ->when($this->search && $this->view === 'home', fn($q) => $q->where('number', 'like', '%' . $this->search . '%'))
            ->withExists(['currentOrder as has_ready_items' => function($query) {
                $query->whereHas('orderItems', function($q) {
                    $q->where('status', \App\Enums\OrderStatus::READY);
                });
            }])
            ->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::where('is_active', true)->get();
    }

    #[Computed]
    public function menuItems()
    {
        return MenuItem::with(['modifiers', 'categoryInfo'])
            ->when($this->selectedCategoryId, fn($q) => $q->where('category_id', $this->selectedCategoryId))
            ->when($this->menuSearch, fn($q) => $q->where('name', 'like', '%' . $this->menuSearch . '%'))
            ->get();
    }

    #[Computed]
    public function selectedTablesLabel()
    {
        if ($this->currentOrder) {
            return $this->currentOrder->table_label;
        }

        return Table::whereIn('id', $this->selectedTableIds)
            ->pluck('number')->sort()->values()->implode('+');
    }

    #[Computed]
    public function notifications()
    {
        if (!Auth::user()->notifications_enabled) {
            return collect();
        }

        return Notification::where('user_id', Auth::id())
            ->orWhereNull('user_id')
            ->latest()
            ->take(20)
            ->get();
    }

    #[Computed]
    public function currentOrder()
    {
        return $this->currentOrderId
            ? Order::with(['orderItems.menuItem', 'orderItems.kot', 'tables', 'creator', 'paymentTransactions'])->find($this->currentOrderId)
            : null;
    }

    #[Computed]
    public function canServeAnyReady()
    {
        $order = $this->currentOrder;
        if (!$order) return false;

        return $order->orderItems->contains(function($item) {
            return $item->status === OrderStatus::READY;
        });
    }

    #[Computed]
    public function enabledPaymentMethods()
    {
        $settings = \App\Models\Setting::first();
        return $settings->enabled_payment_methods ?? ['cash', 'card', 'upi'];
    }

    #[Computed]
    public function discountPresets()
    {
        $settings = \App\Models\Setting::first();
        return $settings->discount_presets ?? [5, 10, 15, 20];
    }

    #[Computed]
    public function canCheckout()
    {
        $order = $this->currentOrder;
        if (!$order) return false;

        return $order->orderItems->every(function($item) {
            return in_array($item->status, [OrderStatus::SERVED, OrderStatus::CANCELLED]);
        });
    }

    #[Computed]
    public function pendingItemsCount()
    {
        $order = $this->currentOrder;
        if (!$order) return 0;

        return $order->orderItems->filter(function($item) {
            return !in_array($item->status, [OrderStatus::SERVED, OrderStatus::CANCELLED]);
        })->count();
    }

    #[Computed]
    public function activeTablesCount()
    {
        return Table::where('status', '!=', \App\Enums\TableStatus::FREE)->count();
    }

    #[Computed]
    public function unreadCount()
    {
        if (!Auth::user()->notifications_enabled) {
            return 0;
        }

        return Notification::where(function($q) {
            $q->where('user_id', Auth::id())->orWhereNull('user_id');
        })->where('read', false)->count();
    }

    #[Computed]
    public function hasCriticalIssues()
    {
        if ($this->view === 'alerts') return false;
        if (!Auth::user()->notifications_enabled) return false;

        // 1. Check for unread alerts (Critical/Urgent types)
        $criticalNotifications = Notification::where(function($q) {
                $q->where('user_id', Auth::id())->orWhereNull('user_id');
            })
            ->where('read', false)
            ->whereIn('type', [\App\Enums\NotificationType::ALERT, \App\Enums\NotificationType::PAYMENT])
            ->exists();

        if ($criticalNotifications) return true;

        // 2. Check for inconsistent table states (e.g., Table is cleaning but has unpaid orders)
        // This is a production-grade check to prevent stale UI flags
        return false;
    }

    #[Layout('layouts.waiter')]
    public function render(\App\Services\BillingService $billingService)
    {
        $totals = ['subtotal' => 0, 'taxTotal' => 0, 'serviceCharge' => 0, 'discountTotal' => 0, 'grandTotal' => 0, 'alreadyPaid' => 0, 'remainingDue' => 0];

        // Context-aware totals
        if (in_array($this->view, ['bill'])) {
            // Full order totals from DB
            if ($this->currentOrder) {
                $settings = \App\Models\Setting::first();
                $this->taxEnabled = $settings?->tax_enabled ?? true;
                $this->taxPercent = (float)($settings?->tax_rate ?? 5);

                $totals = $billingService->calculateOrderTotals($this->currentOrder);

                // Add dynamic discount from UI on top of what's stored on the order
                $discountVal = is_numeric($this->discountValue) ? (float) $this->discountValue : 0;
                if ($discountVal > 0) {
                    $actualDiscount = $this->discountType === 'percentage'
                        ? ($totals['subtotal'] * ($discountVal / 100))
                        : $discountVal;

                    $totals['discountTotal'] += round($actualDiscount, 2);
                    // Recalculate taxable amount and tax based on settings
                    $taxableAmount = round(($totals['subtotal'] + $totals['serviceCharge']) - $totals['discountTotal'], 2);
                    $totals['taxTotal'] = $this->taxEnabled ? round(max(0, $taxableAmount * ($this->taxPercent / 100)), 2) : 0;
                    $totals['grandTotal'] = round($taxableAmount + $totals['taxTotal'], 2);
                    $totals['remainingDue'] = round($totals['grandTotal'] - $totals['alreadyPaid'], 2);
                }
            }
        } else {
            // Cart-based totals for active ordering
            $settings = \App\Models\Setting::first();
            $this->taxEnabled = $settings?->tax_enabled ?? true;
            $this->taxPercent = (float)($settings?->tax_rate ?? 5);

            $subtotal = $this->calculateSubtotal();
            $discount = $this->calculateDiscountAmount($subtotal);
            
            $taxableAmount = $subtotal - $discount;
            $tax = $this->taxEnabled ? $this->calculateTax($taxableAmount, $this->taxPercent) : 0;
            
            $totals['subtotal'] = $subtotal;
            $totals['discountTotal'] = $discount;
            $totals['taxTotal'] = $tax;
            $totals['grandTotal'] = $taxableAmount + $tax;
            $totals['remainingDue'] = $totals['grandTotal'];
        }

        if ($this->view === 'alerts') {
            $this->markAllAsRead();
        }

        return view('livewire.waiter.dashboard', [
            'user' => Auth::user(),
            'totals' => $totals,
            'currency' => \App\Models\Setting::first()?->currency ?? '₹'
        ]);
    }

    private function calculateSubtotal()
    {
        $subtotal = 0;
        foreach ($this->cart as $item) {
            $itemPrice = $item['price'];
            $modifiersPrice = array_sum(array_column($item['modifiers'], 'price'));
            $subtotal += ($itemPrice + $modifiersPrice) * $item['quantity'];
        }
        return $subtotal;
    }

    private function calculateDiscountAmount($subtotal)
    {
        if ($this->discountType === 'percentage') {
            return $subtotal * ($this->discountValue / 100);
        }
        return min($subtotal, $this->discountValue);
    }

    private function calculateTax($amount, $rate = null)
    {
        $settings = \App\Models\Setting::first();
        if (!$settings || !$settings->tax_enabled) return 0;
        
        $rate = $rate ?? (float)$settings->tax_rate;
        return $amount * ($rate / 100);
    }

    public function setView($view)
    {
        if ($view === 'home') {
            $this->cart = [];
            $this->selectedTableIds = [];
            $this->currentOrderId = null;
        }

        $this->view = $view;
        if ($view === 'alerts') {
            $this->markAllAsRead();
        }
        $this->dispatch('view-changed', $view);
    }

    public function markAllAsRead()
    {
        Notification::where(function($q) {
            $q->where('user_id', Auth::id())->orWhereNull('user_id');
        })->where('read', false)->update(['read' => true]);
    }

    public function clearAllNotifications()
    {
        Notification::where(function($q) {
            $q->where('user_id', Auth::id())->orWhereNull('user_id');
        })->delete();
        
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Notifications cleared']);
    }

    public function toggleNotifications()
    {
        $user = Auth::user();
        $user->update([
            'notifications_enabled' => !$user->notifications_enabled
        ]);
        
        $status = $user->notifications_enabled ? 'enabled' : 'disabled';
        $this->dispatch('notify', ['type' => 'info', 'message' => "Notifications {$status}"]);
    }

    public function toggleTableSelection($tableId, \App\Services\OrderService $orderService)
    {
        $table = Table::find($tableId);

        if (!$table) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Table not found']);
            return;
        }

        // Tapping a table with an open order jumps straight to that order's bill
        $openOrder = $orderService->openOrderForTable($table);
        if ($openOrder) {
            $this->currentOrderId = $openOrder->id;
            $this->selectedTableIds = [];
            $this->view = 'bill';
            $this->dispatch('view-changed');
            return;
        }

        if (in_array($table->status, [TableStatus::CLEANING, TableStatus::OUT_OF_SERVICE], true)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => "Table {$table->number} is not available right now."]);
            return;
        }

        // Free / reserved / admin-checked-in table: toggle multi-selection
        if (in_array($tableId, $this->selectedTableIds)) {
            $this->selectedTableIds = array_values(array_diff($this->selectedTableIds, [$tableId]));
        } else {
            $this->selectedTableIds[] = $tableId;
        }
    }

    public function startOrder()
    {
        if (empty($this->selectedTableIds)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Select at least one table first.']);
            return;
        }

        // Order is created lazily on the first kitchen submit
        $this->currentOrderId = null;
        $this->cart = [];
        $this->discountValue = 0;
        $this->view = 'menu';
        $this->dispatch('view-changed');
    }

    public function selectCategory($id)
    {
        $this->selectedCategoryId = $id;
    }

    public function addToCart($itemId)
    {
        $item = MenuItem::with('modifiers')->find($itemId);
        if (!$item || !$item->available) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'This item is currently out of stock.']);
            return;
        }

        if ($item->modifiers->count() > 0) {
            $this->modifierItem = $item;
            $this->selectedModifiers = [];
            $this->showModifiers = true;
            return;
        }

        $this->confirmAddToCart($item, []);
    }

    public function confirmModifiers()
    {
        if (!$this->modifierItem) return;

        $modifiers = $this->modifierItem->modifiers()
            ->whereIn('id', $this->selectedModifiers)
            ->get()
            ->toArray();

        $this->confirmAddToCart($this->modifierItem, $modifiers);
        $this->showModifiers = false;
        $this->modifierItem = null;
    }

    private function confirmAddToCart($item, $modifiers = [])
    {
        $cartKey = (string) Str::uuid();
        
        $this->cart[$cartKey] = [
            'item_id' => $item->id,
            'name' => $item->name,
            'price' => (float) $item->price,
            'quantity' => 1,
            'category' => $item->categoryInfo->name ?? '',
            'is_veg' => $item->is_veg,
            'thumbnail_url' => $item->thumbnail_url,
            'modifiers' => $modifiers,
            'notes' => ''
        ];
        
        $this->dispatch('cart-updated');
    }

    public function updateQuantity($cartKey, $delta)
    {
        if (!isset($this->cart[$cartKey])) return;

        $this->cart[$cartKey]['quantity'] += $delta;
        
        if ($this->cart[$cartKey]['quantity'] <= 0) {
            unset($this->cart[$cartKey]);
        }
        $this->dispatch('cart-updated');
    }

    public function updateItemNotes($cartKey, $notes)
    {
        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['notes'] = $notes;
        }
    }

    public function setDiscount($type, $value)
    {
        $this->discountType = $type;
        $this->discountValue = (float) $value;
    }

    public function submitOrder(\App\Services\OrderService $orderService, \App\Services\KOTService $kotService)
    {
        if (empty($this->cart)) return;

        try {
            DB::transaction(function () use ($orderService, $kotService) {
                // 1. Ensure the order exists (created lazily on the first round)
                if (!$this->currentOrderId) {
                    if (empty($this->selectedTableIds)) throw new \Exception("No table selected");
                    $order = $orderService->createOrder($this->selectedTableIds, Auth::id());
                    $this->currentOrderId = $order->id;
                    $this->selectedTableIds = [];
                } else {
                    $order = Order::lockForUpdate()->findOrFail($this->currentOrderId);
                }

                // 2. Create Order Items as PENDING
                foreach ($this->cart as $data) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $data['item_id'],
                        'quantity' => $data['quantity'],
                        'notes' => $data['notes'],
                        'modifiers' => $data['modifiers'],
                        'status' => OrderStatus::PENDING,
                    ]);
                }

                // 3. Generate KOT and send to Kitchen
                $kotService->sendToKitchen($order);
            });

            $this->cart = [];
            $this->view = 'bill';
            $this->dispatch('notify', ['type' => 'success', 'message' => 'KOT sent to kitchen!']);
            $this->dispatch('view-changed');

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Submission failed: ' . $e->getMessage()]);
        }
    }


    public function addMoreItems()
    {
        $this->view = 'menu';
        $this->cart = [];
        $this->dispatch('view-changed');
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('waiter.login');
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
        // This is now handled via Alpine store, but kept for legacy or manual calls
        $user = Auth::user();
        $newTheme = $user->theme === 'light' ? 'dark' : 'light';
        $user->update(['theme' => $newTheme]);
        
        $this->dispatch('theme-updated', theme: $newTheme);
    }

    public function markItemAsServed($orderItemId, \App\Services\OrderService $orderService)
    {
        $item = OrderItem::find($orderItemId);
        if ($item) {
            $item->update([
                'status' => OrderStatus::SERVED,
                'served_at' => now(),
                'served_by' => Auth::id()
            ]);

            // Sync order status
            $orderService->syncOrderStatus($item->order);

            // Notify Kitchen (Fail-safe synchronous broadcast)
            try {
                if ($item->kot) {
                    broadcast(new \App\Events\KOTCreated($item->kot))->toOthers();
                }
            } catch (\Exception $e) {
                // Log broadcast failure but don't crash the user's action
                \Illuminate\Support\Facades\Log::warning("Kitchen broadcast failed: " . $e->getMessage());
            }

            $this->dispatch('notify', ['type' => 'success', 'message' => "{$item->menuItem->name} served"]);
        }
    }

    public function markAllAsServed(\App\Services\OrderService $orderService)
    {
        if (!$this->currentOrderId) return;

        try {
            DB::transaction(function () use ($orderService) {
                $order = Order::find($this->currentOrderId);
                if (!$order) return;

                OrderItem::where('order_id', $order->id)
                    ->whereIn('status', [
                        OrderStatus::READY,
                        OrderStatus::SENT,
                        OrderStatus::PREPARING
                    ])
                    ->update([
                        'status' => OrderStatus::SERVED,
                        'served_at' => now(),
                        'served_by' => Auth::id()
                    ]);

                $orderService->syncOrderStatus($order);
            });

            $this->dispatch('notify', ['type' => 'success', 'message' => 'All ready items marked as served']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to mark all items as served: " . $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Failed to update items: ' . $e->getMessage()]);
        }
    }

    public function initiatePayment()
    {
        $this->showPaymentModal = true;
    }

    public function markAsPaid($method = null)
    {
        if (!$this->currentOrderId) return;
        if ($method) $this->paymentMethod = $method;

        try {
            $billingService = new \App\Services\BillingService();
            $orderService = new \App\Services\OrderService();
            $paidAmount = 0;
            $order = null;

            DB::transaction(function () use ($billingService, $orderService, &$paidAmount, &$order) {
                $order = Order::lockForUpdate()->findOrFail($this->currentOrderId);

                // Persist the UI-selected discount BEFORE computing the amount due
                $discountInput = is_numeric($this->discountValue) ? (float) $this->discountValue : 0;
                if ($discountInput > 0) {
                    $totals = $billingService->calculateOrderTotals($order);
                    $finalDiscountValue = ($this->discountType === 'percentage')
                        ? ($totals['subtotal'] * ($discountInput / 100))
                        : $discountInput;

                    $order->update([
                        'discount_type' => $this->discountType,
                        'discount_value' => round($finalDiscountValue, 2),
                    ]);
                    $order->unsetRelation('orderItems');
                }

                $totals = $billingService->calculateOrderTotals($order->fresh(['orderItems.menuItem', 'paymentTransactions']));
                $paidAmount = $totals['remainingDue'];

                $orderService->settleOrder($order, $this->paymentMethod ?? 'cash', $paidAmount);

                $order->update(['total' => $totals['grandTotal']]);
            });

            $this->showPaymentModal = false;
            $this->discountValue = 0;
            unset($this->currentOrder);

            // Broadcast payment for Admin notifications
            event(new \App\Events\PaymentReceived($order, $paidAmount, $this->paymentMethod));

            $this->dispatch('notify', ['type' => 'success', 'message' => 'Payment recorded via ' . strtoupper($this->paymentMethod) . '. Tables released for cleaning.']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Payment process failed: " . $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Payment failed: ' . $e->getMessage()]);
        }
    }

    public function freeTables(\App\Services\OrderService $orderService)
    {
        if (!$this->currentOrderId) return;

        if (!$this->canCheckout) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Cannot checkout: Some items are still being prepared or are ready for pickup.']);
            return;
        }

        try {
            DB::transaction(function () use ($orderService) {
                $order = Order::lockForUpdate()->findOrFail($this->currentOrderId);

                if (!$order->is_paid) {
                    // Auto-settle without a recorded transaction (walked-out / comped)
                    $order->update([
                        'is_paid' => true,
                        'payment_method' => 'cash',
                        'status' => OrderStatus::DELIVERED,
                    ]);
                }

                $orderService->releaseTables($order);
            });

            $this->view = 'home';
            $this->currentOrderId = null;
            $this->selectedTableIds = [];
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Tables are now in cleaning state.']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Table free failure: " . $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Checkout failed: ' . $e->getMessage()]);
        }
    }
    
    public function markCleaned($tableId)
    {
        Table::where('id', $tableId)->update(['status' => TableStatus::FREE]);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Table ready for new guests.']);
    }

    public function printBill()
    {
        if (!$this->currentOrderId) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No order to print']);
            return;
        }

        // Open the on-screen preview; actual printing / save-as-PDF happens from there
        $this->dispatch('show-receipt-preview');
    }
}
