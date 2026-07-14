@php
    $settings = \App\Models\Setting::first();
    $order = $this->currentOrder;
    $allItems = $order ? $order->orderItems : collect();
    
    // Group items by menu_item_id to sum quantities
    $groupedItems = $allItems->groupBy('menu_item_id')->map(function ($items) {
        $first = $items->first();
        return (object)[
            'name' => $first->menuItem->name,
            'quantity' => $items->sum('quantity'),
            'price' => $first->price ?? $first->menuItem->price,
        ];
    });

    $taxTotal = (float)($totals['taxTotal'] ?? 0);
    $halfTax = $taxTotal / 2;
    $currency = $settings?->currency ?? '₹';
@endphp

<div id="printable-receipt" class="hidden print:block font-mono text-[8px] text-black bg-white w-[80mm] p-2 leading-tight">
    <!-- Top Order ID -->
    <div class="text-center font-bold mb-1 uppercase text-[9px]">
        *** {{ $order->order_number ?? 'POS' }} ***
    </div>

    <!-- Header -->
    <div class="text-center mb-3">
        <h1 class="text-[11px] font-black uppercase mb-1">{{ $settings?->restaurant_name ?? 'RESTAURANT' }}</h1>
        <p class="text-[8px] uppercase font-bold">{{ $settings?->restaurant_address ?? 'RESTAURANT ADDRESS' }}</p>
        @if($settings?->gst_no)
            <p class="text-[8px] uppercase font-bold">GSTIN: {{ $settings?->gst_no }}</p>
        @endif
        <div class="mt-4 mb-2">
            <h2 class="text-[9px] font-bold uppercase underline decoration-1 underline-offset-2">{{ ($totals['taxTotal'] ?? 0) > 0 ? 'TAX INVOICE' : 'INVOICE' }}</h2>
        </div>
    </div>

    <!-- Bill Info -->
    <div class="space-y-0.5 mb-2 text-[8px] font-bold">
        <div class="flex justify-between">
            <span>BILL NO. : #{{ $order->order_number ?? 'N/A' }}</span>
            <span>DATE: {{ now()->format('d/m/y') }}</span>
        </div>
        <div class="flex justify-between">
            <span>TABLE : {{ $order->table_label ?? 'N/A' }}</span>
            <span>WAITER: {{ substr($order->creator->name ?? 'N/A', 0, 8) }}</span>
        </div>
    </div>

    <div class="border-b border-black border-dashed my-2"></div>

    <!-- Items Table -->
    <table class="w-full mb-2 border-collapse text-[8px] font-bold">
        <thead>
            <tr class="border-b border-black border-dashed">
                <th class="text-left py-1 w-[45%]">DESCRIPTION</th>
                <th class="text-center py-1 w-[10%]">QTY</th>
                <th class="text-right py-1 w-[20%]">RATE</th>
                <th class="text-right py-1 w-[25%]">AMT</th>
            </tr>
        </thead>
        <tbody class="border-b border-black border-dashed">
            @foreach($groupedItems as $item)
                <tr class="align-top border-b border-black border-dotted last:border-0">
                    <td class="py-1 uppercase break-words pr-1">{{ $item->name }}</td>
                    <td class="text-center py-1">{{ $item->quantity }}</td>
                    <td class="text-right py-1">{{ number_format($item->price, 2) }}</td>
                    <td class="text-right py-1">{{ number_format($item->price * $item->quantity, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary -->
    <div class="space-y-0.5 text-[8px] font-bold">
        <div class="flex justify-between">
            <span>SUB TOTAL :</span>
            <span>{{ number_format($totals['subtotal'], 2) }}</span>
        </div>
        @if($totals['discountTotal'] > 0)
            <div class="flex justify-between">
                <span>DISCOUNT :</span>
                <span>-{{ number_format($totals['discountTotal'], 2) }}</span>
            </div>
        @endif
        @if(($totals['taxTotal'] ?? 0) > 0)
            @php
                $displayRate = (float)($taxPercent ?? ($settings?->tax_rate ?? 5)) / 2;
            @endphp
            <div class="flex justify-between">
                <span>CGST @ {{ $displayRate }}% :</span>
                <span>{{ number_format($halfTax, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span>SGST @ {{ $displayRate }}% :</span>
                <span>{{ number_format($halfTax, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span>TOTAL GST :</span>
                <span>{{ number_format($taxTotal, 2) }}</span>
            </div>
        @endif
        <div class="border-b border-black border-dashed my-1.5"></div>
        <div class="flex justify-between text-[10px] font-black">
            <span>NET TOTAL:</span>
            <span>{{ $currency }} {{ number_format($totals['grandTotal'], 2) }}</span>
        </div>
        <div class="border-b border-black border-dashed my-1.5"></div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-4 space-y-1.5 uppercase font-bold text-[8px]">
        <p>(TIME: {{ now()->format('h:i A') }})</p>
        <p class="text-[9px] font-black">{{ $settings?->receipt_footer ?? 'THANK YOU FOR YOUR BUSINESS!' }}</p>
    </div>
</div>
