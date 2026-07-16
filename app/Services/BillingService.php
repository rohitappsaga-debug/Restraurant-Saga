<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

class BillingService
{
    /**
     * Calculate all totals for a single order. Same return shape as the
     * old session totals so views keep their keys.
     */
    public function calculateOrderTotals(Order $order): array
    {
        $order->loadMissing(['orderItems.menuItem', 'paymentTransactions']);

        $subtotal = $order->orderItems->sum(function ($item) {
            return $item->menuItem->price * $item->quantity;
        });

        $serviceCharge = (float) $order->service_charge;
        $discountTotal = (float) $order->discount_value;
        $alreadyPaid = (float) $order->paymentTransactions->where('status', 'completed')->sum('amount');

        $settings = \App\Models\Setting::current();
        $taxEnabled = $settings?->tax_enabled ?? true;
        $taxRate = (float) ($settings?->tax_rate ?? 5);

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
