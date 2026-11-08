<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin() || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->is_active
            && $user->isAdmin()
            && $user->id !== $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->is_active
            && $user->isAdmin()
            && $user->id !== $model->id;
    }

    public function deactivate(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    public function activate(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    public function approve(User $user, User $model): bool
    {
        return $user->is_active
            && $user->isAdmin()
            && $model->isEmployee()
            && ! $model->is_approved;
    }
}
