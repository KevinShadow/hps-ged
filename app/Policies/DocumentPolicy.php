<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Document $document): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->can(
            'view',
            $document->monthlySubmission
        );
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function upload(User $user, Document $document): bool
    {
        return false;
    }

    public function delete(User $user, Document $document): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $submission = $document->monthlySubmission;

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEmployee()
            && $user->id === $submission->user_id
            && $submission->isEditable();
    }
}
