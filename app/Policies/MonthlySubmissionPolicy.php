<?php

namespace App\Policies;

use App\Enums\SubmissionStatus;
use App\Models\MonthlySubmission;
use App\Models\User;

class MonthlySubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(
        User $user,
        MonthlySubmission $submission
    ): bool {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isManager()) {
            return $user->department_id === $submission->department_id;
        }

        return $user->isEmployee()
            && $user->id === $submission->user_id;
    }

    public function create(User $user): bool
    {
        return $user->is_active
            && $user->isEmployee()
            && $user->department_id !== null;
    }

    public function update(
        User $user,
        MonthlySubmission $submission
    ): bool {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin()
            || (
                $user->isEmployee()
                && $user->id === $submission->user_id
                && $submission->isEditable()
            );
    }

    public function submit(
        User $user,
        MonthlySubmission $submission
    ): bool {
        return $user->is_active
            && $user->isEmployee()
            && $user->id === $submission->user_id
            && $submission->isEditable();
    }

    public function approve(
        User $user,
        MonthlySubmission $submission
    ): bool {
        if (! $user->is_active) {
            return false;
        }

        if ($submission->status !== SubmissionStatus::SUBMITTED) {
            return false;
        }

        return $user->isAdmin()
            || (
                $user->isManager()
                && $user->department_id === $submission->department_id
            );
    }

    public function reject(
        User $user,
        MonthlySubmission $submission
    ): bool {
        return $this->approve($user, $submission);
    }
}
