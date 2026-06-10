<?php
namespace App\Repositories;
use App\Models\PaymentTransaction;

class PaymentRepository extends BaseRepository {
    public function __construct(PaymentTransaction $model) {
        parent::__construct($model);
    }
}