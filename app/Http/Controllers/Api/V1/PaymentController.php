<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PaymentTransactionResource;
use App\Models\PaymentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    /** Read-only listing — payments are recorded through order settlement. */
    public function index(Request $request): JsonResponse
    {
        $payments = PaymentTransaction::query()
            ->when($request->query('order_id'), fn ($q, $id) => $q->where('order_id', $id))
            ->when($request->query('method'), fn ($q, $m) => $q->where('method', $m))
            ->when($request->query('date_from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('date_to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(min((int) $request->query('limit', 20), 100));

        return $this->respond(PaymentTransactionResource::collection($payments));
    }
}
