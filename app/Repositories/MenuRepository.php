<?php
namespace App\Repositories;

use App\Models\MenuItem;

class MenuRepository extends BaseRepository
{
    public function __construct(MenuItem $model)
    {
        parent::__construct($model);
    }

    public function getAvailableItems()
    {
        return $this->model->where('available', true)->with(['categoryData', 'modifiers'])->get();
    }
}