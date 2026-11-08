<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\MonthlySubmissionRepositoryInterface;
use App\Enums\SubmissionStatus;
use App\Models\MonthlySubmission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MonthlySubmissionRepository implements MonthlySubmissionRepositoryInterface
{
    public function __construct(
        protected MonthlySubmission $model
    ) {}

    public function findById(int $id): ?MonthlySubmission
    {
        return $this->model
            ->with(['user', 'department', 'documents.uploader'])
            ->find($id);
    }

    public function findByIdForUpdate(int $id): ?MonthlySubmission
    {
        return $this->model
            ->newQuery()
            ->lockForUpdate()
            ->find($id);
    }

    public function findForUserPeriod(
        int $userId,
        int $year,
        int $month
    ): ?MonthlySubmission {
        return $this->model
            ->with('department')
            ->withCount('documents')
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    public function firstOrCreateForUserPeriod(
        int $userId,
        int $departmentId,
        int $year,
        int $month
    ): MonthlySubmission {
        return $this->model->firstOrCreate(
            [
                'user_id' => $userId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'department_id' => $departmentId,
                'status' => SubmissionStatus::DRAFT,
            ]
        );
    }

    public function paginateByDepartment(
        int $departmentId,
        int $year,
        ?int $month = null,
        ?SubmissionStatus $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->model
            ->with(['user', 'department'])
            ->withCount('documents')
            ->where('department_id', $departmentId)
            ->where('year', $year)
            ->when(
                $month !== null,
                fn ($query) => $query->where('month', $month)
            )
            ->when(
                $status !== null,
                fn ($query) => $query->where('status', $status->value)
            )
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForAdministration(
        int $year,
        ?int $month = null,
        ?int $departmentId = null,
        ?SubmissionStatus $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->model
            ->with(['user', 'department'])
            ->withCount('documents')
            ->where('year', $year)
            ->when(
                $month !== null,
                fn ($query) => $query->where('month', $month)
            )
            ->when(
                $departmentId !== null,
                fn ($query) => $query->where('department_id', $departmentId)
            )
            ->when(
                $status !== null,
                fn ($query) => $query->where('status', $status->value)
            )
            ->latest()
            ->paginate($perPage);
    }

    public function updateIfStatus(
        int $id,
        SubmissionStatus $expectedStatus,
        array $data
    ): bool {
        return $this->model
            ->whereKey($id)
            ->where('status', $expectedStatus->value)
            ->update($data) === 1;
    }

    public function create(array $data): MonthlySubmission
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $submission = $this->findById($id);

        if (! $submission) {
            return false;
        }

        return $submission->update($data);
    }
}
