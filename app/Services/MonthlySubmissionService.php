<?php

namespace App\Services;

use App\Contracts\Repositories\MonthlySubmissionRepositoryInterface;
use App\Enums\SubmissionStatus;
use App\Models\MonthlySubmission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class MonthlySubmissionService
{
    public function __construct(
        protected MonthlySubmissionRepositoryInterface $repository
    ) {}

    public function getSubmission(int $id): ?MonthlySubmission
    {
        return $this->repository->findById($id);
    }

    /**
     * @return array{allowed: bool, reopened: bool}
     */
    public function prepareForAdditionalDocuments(
        int $id,
        bool $reopenForAdditionalDocuments = true
    ): array {
        $submission = $this->repository->findByIdForUpdate($id);

        if (! $submission) {
            return ['allowed' => false, 'reopened' => false];
        }

        if ($submission->isSubmitted() || $submission->isApproved()) {
            if (! $reopenForAdditionalDocuments) {
                return ['allowed' => true, 'reopened' => false];
            }

            $submission->status = SubmissionStatus::DRAFT;
            $submission->submitted_at = null;

            return [
                'allowed' => $submission->save(),
                'reopened' => true,
            ];
        }

        return [
            'allowed' => $submission->isEditable(),
            'reopened' => false,
        ];
    }

    public function getUserSubmission(
        int $userId,
        int $year,
        int $month
    ): ?MonthlySubmission {
        return $this->repository->findForUserPeriod(
            $userId,
            $year,
            $month
        );
    }

    public function getDepartmentSubmissions(
        int $departmentId,
        int $year,
        ?int $month = null,
        ?SubmissionStatus $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->repository->paginateByDepartment(
            $departmentId,
            $year,
            $month,
            $status,
            $perPage
        );
    }

    public function getAllSubmissions(
        int $year,
        ?int $month = null,
        ?int $departmentId = null,
        ?SubmissionStatus $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->repository->paginateForAdministration(
            $year,
            $month,
            $departmentId,
            $status,
            $perPage
        );
    }

    public function createSubmission(
        int $userId,
        int $departmentId,
        int $year,
        int $month
    ): MonthlySubmission {
        if ($month < 1 || $month > 12) {
            throw ValidationException::withMessages([
                'month' => 'Le mois doit être compris entre 1 et 12.',
            ]);
        }

        return $this->repository->firstOrCreateForUserPeriod(
            $userId,
            $departmentId,
            $year,
            $month
        );
    }

    public function submitSubmission(int $id): bool
    {
        $submission = $this->repository->findById($id);

        if (! $submission) {
            throw ValidationException::withMessages([
                'submission' => 'Dossier introuvable.',
            ]);
        }

        if (! $submission->isEditable()) {
            throw ValidationException::withMessages([
                'submission' => 'Seul un brouillon ou un dossier refusé peut être transmis.',
            ]);
        }

        if ($submission->documents()->doesntExist()) {
            throw ValidationException::withMessages([
                'documents' => 'Ajoutez au moins un document avant de soumettre le dossier.',
            ]);
        }

        if (! $this->repository->updateIfStatus(
            $id,
            $submission->status,
            [
                'status' => SubmissionStatus::SUBMITTED,
                'submitted_at' => now(),
            ]
        )) {
            throw ValidationException::withMessages([
                'submission' => 'Le dossier a déjà été modifié.',
            ]);
        }

        return true;
    }

    public function approveSubmission(int $id): bool
    {
        return $this->reviewSubmission(
            $id,
            SubmissionStatus::APPROVED
        );
    }

    public function rejectSubmission(int $id): bool
    {
        return $this->reviewSubmission(
            $id,
            SubmissionStatus::REJECTED
        );
    }

    private function reviewSubmission(
        int $id,
        SubmissionStatus $newStatus
    ): bool {
        $submission = $this->repository->findById($id);

        if (! $submission) {
            throw ValidationException::withMessages([
                'submission' => 'Dossier introuvable.',
            ]);
        }

        if ($submission->status !== SubmissionStatus::SUBMITTED) {
            throw ValidationException::withMessages([
                'submission' => 'Seul un dossier transmis peut être traité.',
            ]);
        }

        if (! $this->repository->updateIfStatus(
            $id,
            SubmissionStatus::SUBMITTED,
            ['status' => $newStatus]
        )) {
            throw ValidationException::withMessages([
                'submission' => 'Le dossier a déjà été traité.',
            ]);
        }

        return true;
    }
}
