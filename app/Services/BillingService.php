<?php

namespace App\Services;

use App\Models\Order;
use App\Models\TableSession;
use Illuminate\Support\Collection;

class BillingService
{
    /**
     * Calculate all totals for a table session.
     */
    public function calculateSessionTotals(TableSession $session): array
    {
        $orders = $session->orders()->with(['orderItems.menuItem', 'paymentTransactions'])->get();
        
        $subtotal = 0;
        $taxTotal = 0;
        $serviceCharge = 0;
        $discountTotal = 0;
        $alreadyPaid = 0;

        foreach ($orders as $order) {
            $orderSubtotal = $order->orderItems->sum(function ($item) {
                return $item->menuItem->price * $item->quantity;
            });

            $subtotal += $orderSubtotal;
            $serviceCharge += $order->service_charge;
            $discountTotal += $order->discount_value;
            
            $alreadyPaid += $order->paymentTransactions->where('status', 'completed')->sum('amount');
        }

        // Apply GST based on system settings
        $settings = \App\Models\Setting::first();
        $taxEnabled = $settings?->tax_enabled ?? true;
        $taxRate = (float)($settings?->tax_rate ?? 5);

        $taxableAmount = round(($subtotal + $serviceCharge) - $discountTotal, 2);
        $taxTotal = $taxEnabled ? round($taxableAmount * ($taxRate / 100), 2) : 0;

        $grandTotal = round($taxableAmount + $taxTotal, 2);
        $remainingDue = round($grandTotal - $alreadyPaid, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'taxTotal' => $taxTotal,
            'serviceCharge' => round($serviceCharge, 2),
            'discountTotal' => round($discountTotal, 2),
            'grandTotal' => $grandTotal,
            'alreadyPaid' => round($alreadyPaid, 2),
            'remainingDue' => max(0, $remainingDue),
        ];
    }
}
