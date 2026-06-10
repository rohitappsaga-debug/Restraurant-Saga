<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function getOrdersWithPaginationAndFilters(int $page, int $limit, array $filters): LengthAwarePaginator
    {
        $query = $this->model->with(['orderItems.menuItem', 'creator:id,name,email']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['tableNumber'])) {
            $query->where('table_number', $filters['tableNumber']);
        }
        if (isset($filters['dateFrom'])) {
            $query->where('created_at', '>=', $filters['dateFrom']);
        }
        if (isset($filters['dateTo'])) {
            $query->where('created_at', '<=', $filters['dateTo']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);
    }

    public function findOrderWithDetails(string $id): ?Order
    {
        return $this->model->with(['orderItems.menuItem', 'creator:id,name,email', 'paymentTransactions'])->find($id);
    }
    
    public function getOrdersByTableNumber(int $tableNumber)
    {
        return $this->model->where('table_number', $tableNumber)
            ->with(['orderItems.menuItem', 'creator:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
