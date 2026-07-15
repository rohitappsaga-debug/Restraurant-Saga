<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'type' => $this->type,
            'table_number' => $this->table_number,
            'table_label' => $this->whenLoaded('tables', fn () => $this->table_label),
            'tables' => TableResource::collection($this->whenLoaded('tables')),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'kots' => KotResource::collection($this->whenLoaded('kots')),
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'total' => (float) $this->total,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'service_charge' => (float) $this->service_charge,
            'is_paid' => $this->is_paid,
            'payment_method' => $this->payment_method?->value,
            'payments' => PaymentTransactionResource::collection($this->whenLoaded('paymentTransactions')),
            'hold_status' => $this->hold_status,
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
