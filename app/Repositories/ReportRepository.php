<?php
namespace App\Repositories;
use App\Models\DailySale;

class ReportRepository extends BaseRepository {
    public function __construct(DailySale $model) {
        parent::__construct($model);
    }
}