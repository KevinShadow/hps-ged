<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        protected User $model
    ) {}

    public function findById(int $id): ?User
    {
        return $this->model
            ->with('department')
            ->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model
            ->where('email', $email)
            ->first();
    }

    public function getAll(): Collection
    {
        return $this->model
            ->with('department')
            ->orderBy('name')
            ->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with('department')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginateByDepartment(
        int $departmentId,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->model
            ->with('department')
            ->where('department_id', $departmentId)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginatePendingEmployees(
        ?int $departmentId = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->model
            ->with('department')
            ->where('role', UserRole::EMPLOYEE->value)
            ->where('is_approved', false)
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = $this->findById($id);

        if (! $user) {
            return false;
        }

        return $user->update($data);
    }
}
