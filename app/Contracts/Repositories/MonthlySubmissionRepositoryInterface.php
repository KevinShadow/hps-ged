<?php

namespace App\Contracts\Repositories;

use App\Enums\SubmissionStatus;
use App\Models\MonthlySubmission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MonthlySubmissionRepositoryInterface
{
    public function findById(int $id): ?MonthlySubmission;

    public function findByIdForUpdate(int $id): ?MonthlySubmission;

    public function findForUserPeriod(
        int $userId,
        int $year,
        int $month
    ): ?MonthlySubmission;

    public function firstOrCreateForUserPeriod(
        int $userId,
        int $departmentId,
        int $year,
        int $month
    ): MonthlySubmission;

    public function paginateByDepartment(
        int $departmentId,
        int $year,
        ?int $month = null,
        ?SubmissionStatus $status = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function paginateForAdministration(
        int $year,
        ?int $month = null,
        ?int $departmentId = null,
        ?SubmissionStatus $status = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function updateIfStatus(
        int $id,
        SubmissionStatus $expectedStatus,
        array $data
    ): bool;

    public function create(array $data): MonthlySubmission;

    public function update(int $id, array $data): bool;
}
