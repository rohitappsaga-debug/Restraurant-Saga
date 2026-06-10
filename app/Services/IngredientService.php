<?php
namespace App\Services;
use App\Repositories\IngredientRepository;

class IngredientService {
    public function __construct(protected IngredientRepository $repository) {}

    public function getAll() { return $this->repository->all(); }
    public function find($id) { 
        $item = $this->repository->find($id);
        if (!$item) abort(404, "Ingredient not found");
        return $item;
    }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { 
        $item = $this->find($id);
        $this->repository->update($id, $data);
        return $this->find($id);
    }
    public function delete($id) { return $this->repository->delete($id); }
}