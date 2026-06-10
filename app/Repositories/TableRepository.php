<?php
namespace App\Repositories;

use App\Models\Table;
use App\Enums\TableStatus;

class TableRepository extends BaseRepository
{
    public function __construct(Table $model)
    {
        parent::__construct($model);
    }
    
    public function findByNumber($tableNumber)
    {
        return $this->model->where('number', $tableNumber)->first();
    }
    
    public function updateStatus($tableNumber, TableStatus|string $status, ?string $currentOrderId = null): bool
    {
        $table = $this->findByNumber($tableNumber);
        if ($table) {
            $newStatus = $status instanceof TableStatus ? $status : TableStatus::from($status);
            return $table->update([
                'status' => $newStatus,
                'current_order_id' => $currentOrderId
            ]);
        }
        return false;
    }

    public function updateGroupStatus(string $groupId, TableStatus|string $status, ?string $currentOrderId = null)
    {
        $newStatus = $status instanceof TableStatus ? $status : TableStatus::from($status);
        return $this->model->where('group_id', $groupId)->update([
            'status' => $newStatus,
            'current_order_id' => $currentOrderId
        ]);
    }
}