<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function getAllUsers(): Collection
    {
        return $this->userRepository->getAll();
    }

    public function getPaginatedUsers(
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->userRepository->paginate($perPage);
    }

    public function getUsersByDepartment(
        int $departmentId,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->userRepository->paginateByDepartment(
            $departmentId,
            $perPage
        );
    }

    public function getPendingEmployees(
        ?int $departmentId = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->userRepository->paginatePendingEmployees(
            $departmentId,
            $perPage
        );
    }

    public function getUser(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    public function createUser(array $data): User
    {
        $this->ensureEmailIsAvailable($data['email']);

        return $this->userRepository->create($data);
    }

    public function registerEmployee(array $data): User
    {
        $this->ensureEmailIsAvailable($data['email']);

        return $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'department_id' => $data['department_id'],
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
            'is_approved' => false,
        ]);
    }

    public function approveEmployee(int $id): void
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw ValidationException::withMessages([
                'user' => 'Utilisateur introuvable.',
            ]);
        }

        if (! $user->isEmployee() || $user->is_approved) {
            throw ValidationException::withMessages([
                'user' => 'Ce compte ne peut pas être approuvé.',
            ]);
        }

        if (! $this->userRepository->update($id, [
            'is_approved' => true,
            'is_active' => true,
        ])) {
            throw ValidationException::withMessages([
                'user' => 'La validation du compte a échoué.',
            ]);
        }

    }

    public function updateUser(int $id, array $data): bool
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw ValidationException::withMessages([
                'user' => 'Utilisateur introuvable.',
            ]);
        }

        $existingUser = $this->userRepository->findByEmail(
            $data['email']
        );

        if ($existingUser && $existingUser->id !== $user->id) {
            throw ValidationException::withMessages([
                'email' => 'Cette adresse e-mail est déjà utilisée.',
            ]);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $this->userRepository->update($id, $data);
    }

    public function deactivateUser(int $id): bool
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw ValidationException::withMessages([
                'user' => 'Utilisateur introuvable.',
            ]);
        }

        return $this->userRepository->update($id, [
            'is_active' => false,
        ]);
    }

    public function activateUser(int $id): bool
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw ValidationException::withMessages([
                'user' => 'Utilisateur introuvable.',
            ]);
        }

        return $this->userRepository->update($id, [
            'is_active' => true,
        ]);
    }

    private function ensureEmailIsAvailable(string $email): void
    {
        if ($this->userRepository->findByEmail($email)) {
            throw ValidationException::withMessages([
                'email' => 'Cette adresse e-mail est déjà utilisée.',
            ]);
        }
    }
}
