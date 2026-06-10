<?php
namespace App\Repositories;
use App\Models\Ingredient;

class IngredientRepository extends BaseRepository {
    public function __construct(Ingredient $model) {
        parent::__construct($model);
    }
}