<?php
namespace App\Repositories;
use App\Models\DeliveryDetail;

class DeliveryDetailRepository extends BaseRepository {
    public function __construct(DeliveryDetail $model) {
        parent::__construct($model);
    }
}