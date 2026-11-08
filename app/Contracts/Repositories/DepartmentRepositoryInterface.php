<?php

namespace App\Contracts\Repositories;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    public function findById(int $id): ?Department;

    public function findByName(string $name): ?Department;

    public function getAll(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Department;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;
}
