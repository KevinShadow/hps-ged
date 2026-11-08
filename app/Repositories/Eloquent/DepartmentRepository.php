<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\DepartmentRepositoryInterface;
use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function __construct(
        protected Department $model
    ) {}

    public function findById(int $id): ?Department
    {
        return $this->model->find($id);
    }

    public function findByName(string $name): ?Department
    {
        return $this->model
            ->where('name', $name)
            ->first();
    }

    public function getAll(): Collection
    {
        return $this->model
            ->orderBy('name')
            ->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): Department
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $department = $this->findById($id);

        if (! $department) {
            return false;
        }

        return $department->update($data);
    }

    public function delete(int $id): bool
    {
        $department = $this->findById($id);

        if (! $department) {
            return false;
        }

        return (bool) $department->delete();
    }
}
