<?php

namespace App\Services;

use App\Contracts\Repositories\DepartmentRepositoryInterface;
use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public function __construct(
        protected DepartmentRepositoryInterface $departmentRepository
    ) {}

    public function getAllDepartments(): Collection
    {
        return $this->departmentRepository->getAll();
    }

    public function getPaginatedDepartments(int $perPage = 15): LengthAwarePaginator
    {
        return $this->departmentRepository->paginate($perPage);
    }

    public function getDepartment(int $id): ?Department
    {
        return $this->departmentRepository->findById($id);
    }

    public function createDepartment(array $data): Department
    {
        $existingDepartment = $this->departmentRepository
            ->findByName($data['name']);

        if ($existingDepartment) {
            throw ValidationException::withMessages([
                'name' => 'Ce service existe déjà.',
            ]);
        }

        return $this->departmentRepository->create($data);
    }

    public function updateDepartment(int $id, array $data): bool
    {
        $department = $this->departmentRepository->findById($id);

        if (! $department) {
            throw ValidationException::withMessages([
                'department' => 'Service introuvable.',
            ]);
        }

        $existingDepartment = $this->departmentRepository
            ->findByName($data['name']);

        if (
            $existingDepartment &&
            $existingDepartment->id !== $department->id
        ) {
            throw ValidationException::withMessages([
                'name' => 'Ce nom de service est déjà utilisé.',
            ]);
        }

        return $this->departmentRepository->update($id, $data);
    }

    public function deactivateDepartment(int $id): bool
    {
        $department = $this->departmentRepository->findById($id);

        if (! $department) {
            throw ValidationException::withMessages([
                'department' => 'Service introuvable.',
            ]);
        }

        if ($department->users()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'department' => 'Impossible de désactiver un service contenant des employés actifs.',
            ]);
        }

        return $this->departmentRepository->update($id, [
            'is_active' => false,
        ]);
    }
}
