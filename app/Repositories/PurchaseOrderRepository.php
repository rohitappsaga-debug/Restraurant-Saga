<?php
namespace App\Repositories;
use App\Models\PurchaseOrder;

class PurchaseOrderRepository extends BaseRepository {
    public function __construct(PurchaseOrder $model) {
        parent::__construct($model);
    }
}