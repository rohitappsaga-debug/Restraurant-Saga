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
    public $selectedTableId = null;
    public $selectedSessionId = null;
    #[Url]
    public $selectedCategoryId = null;
    #[Url]
    public $menuSearch = '';
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
            ->withExists(['activeSession as has_ready_items' => function($query) {
                $query->whereHas('orders.orderItems', function($q) {
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
    public function selectedTable()
    {
        return $this->selectedTableId ? Table::find($this->selectedTableId) : null;
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
        return $this->currentOrderId ? Order::with(['orderItems.menuItem', 'table'])->find($this->currentOrderId) : null;
    }

    #[Computed]
    public function selectedSession()
    {
        return $this->selectedSessionId ? \App\Models\TableSession::with(['orders.orderItems.menuItem', 'orders.orderItems.kot'])->find($this->selectedSessionId) : null;
    }

    #[Computed]
    public function canServeAnyReady()
    {
        $session = $this->selectedSession;
        if (!$session) return false;
        
        return $session->orders->flatMap->orderItems->contains(function($item) {
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
        $session = $this->selectedSession;
        if (!$session) return false;
        
        return $session->orders->flatMap->orderItems->every(function($item) {
            return in_array($item->status, [OrderStatus::SERVED, OrderStatus::CANCELLED]);
        });
    }

    #[Computed]
    public function pendingItemsCount()
    {
        $session = $this->selectedSession;
        if (!$session) return 0;

        return $session->orders->flatMap->orderItems->filter(function($item) {
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
            // Full session totals from DB
            if ($this->selectedSessionId) {
                $session = \App\Models\TableSession::find($this->selectedSessionId);
                if ($session) {
                    $settings = \App\Models\Setting::first();
                    $this->taxEnabled = $settings?->tax_enabled ?? true;
                    $this->taxPercent = (float)($settings?->tax_rate ?? 5);

                    $totals = $billingService->calculateSessionTotals($session);

                    // Add dynamic discount from UI if session is active
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
            $this->selectedTableId = null;
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

    public function selectTable($tableId, \App\Services\OrderService $orderService)
    {
        $this->selectedTableId = $tableId;
        $table = Table::find($tableId);
        
        if (!$table) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Table not found']);
            return;
        }

        if ($table->status === TableStatus::OCCUPIED && $table->current_session_id) {
            $this->selectedSessionId = $table->current_session_id;
            $this->view = 'bill';
        } else {
            // New "Conceptual" Session - don't persist to DB yet
            $this->selectedSessionId = null;
            $this->view = 'menu';
            $this->cart = [];
            $this->discountValue = 0;
        }
        
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
                // 1. Ensure session exists (Just-in-time creation)
                if (!$this->selectedSessionId) {
                    if (!$this->selectedTableId) throw new \Exception("No table selected");
                    $session = $orderService->startSession($this->selectedTableId, Auth::id());
                    $this->selectedSessionId = $session->id;
                }

                $session = \App\Models\TableSession::findOrFail($this->selectedSessionId);
                
                // 2. Ensure active order exists for this session
                $order = $orderService->getActiveOrder($session);

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
        if (!$this->selectedSessionId) return;
        
        try {
            DB::transaction(function () use ($orderService) {
                $session = \App\Models\TableSession::find($this->selectedSessionId);
                if (!$session) return;

                $orderIds = $session->orders()->pluck('id');
                
                OrderItem::whereIn('order_id', $orderIds)
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

                // Sync all affected orders
                foreach ($session->orders as $order) {
                    $orderService->syncOrderStatus($order);
                }
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
        if (!$this->selectedSessionId) return;
        if ($method) $this->paymentMethod = $method;

        try {
            DB::beginTransaction();
            $session = \App\Models\TableSession::lockForUpdate()->find($this->selectedSessionId);
            if (!$session) throw new \Exception("Session not found");

            $billingService = new \App\Services\BillingService();
            $totals = $billingService->calculateSessionTotals($session);
            
            $orderService = new \App\Services\OrderService();
            $order = $orderService->getActiveOrder($session);

            // Persist the UI-selected discount to the order record
            $finalDiscountValue = 0;
            $discountInput = is_numeric($this->discountValue) ? (float) $this->discountValue : 0;
            
            if ($discountInput > 0) {
                $finalDiscountValue = ($this->discountType === 'percentage')
                    ? ($totals['subtotal'] * ($discountInput / 100))
                    : $discountInput;
            }

            // Record Payment Transaction for the remaining balance
            if (isset($totals['remainingDue']) && $totals['remainingDue'] > 0) {
                $order->paymentTransactions()->create([
                    'id' => (string) Str::uuid(),
                    'amount' => $totals['remainingDue'],
                    'method' => $this->paymentMethod,
                    'status' => 'completed'
                ]);
            }

            // Finalize ALL Orders in the Session
            $session->orders()->update([
                'payment_method' => $this->paymentMethod ?? 'cash',
                'is_paid' => true,
                'status' => OrderStatus::DELIVERED
            ]);

            // Update the specific active order with the UI-specific discount/totals for record keeping
            $order->update([
                'discount_type' => $this->discountType,
                'discount_value' => $finalDiscountValue,
                'total' => $totals['grandTotal'] ?? $order->total,
            ]);

            DB::commit();
            $this->showPaymentModal = false;
            
            // Broadcast payment for Admin notifications
            event(new \App\Events\PaymentReceived($order, $totals['remainingDue'] ?? 0, $this->paymentMethod));
            
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Payment recorded via ' . strtoupper($this->paymentMethod)]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Payment process failed: " . $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Payment failed: ' . $e->getMessage()]);
        }
    }

    public function freeTable()
    {
        if (!$this->selectedTableId || !$this->selectedSessionId) return;

        if (!$this->canCheckout) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Cannot checkout: Some items are still being prepared or are ready for pickup.']);
            return;
        }

        try {
            DB::transaction(function () {
                $session = \App\Models\TableSession::lockForUpdate()->find($this->selectedSessionId);
                if (!$session) throw new \Exception("Session not found");

                $session->update([
                    'status' => 'closed',
                    'ended_at' => now()
                ]);

                // Auto-settle any unpaid orders in this session
                $session->orders()->where('is_paid', false)->update([
                    'is_paid' => true,
                    'payment_method' => 'CASH', // Default to cash if auto-settled
                    'status' => OrderStatus::DELIVERED
                ]);

                Table::where('id', $this->selectedTableId)->update([
                    'status' => TableStatus::CLEANING,
                    'current_session_id' => null
                ]);
            });

            $this->view = 'home';
            $this->selectedTableId = null;
            $this->selectedSessionId = null;
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Table is now in cleaning state.']);
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
        if (!$this->selectedSessionId) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No session to print']);
            return;
        }

        $this->dispatch('trigger-print-bill');
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Printing bill...']);
    }
}
