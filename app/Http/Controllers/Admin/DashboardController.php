<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Document;
use App\Models\MonthlySubmission;
use App\Models\User;
use App\Services\ExternalStorageService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ExternalStorageService $externalStorage
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        $dashboardYear = now()->year;
        $submissionCounts = MonthlySubmission::query()
            ->where('year', $dashboardYear)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $latestSubmissions = MonthlySubmission::query()
            ->with(['user', 'department'])
            ->withCount('documents')
            ->latest()
            ->limit(8)
            ->get();

        $latestDocuments = Document::query()
            ->with([
                'monthlySubmission.user',
                'monthlySubmission.department',
                'uploader',
            ])
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', [
            'dashboardYear' => $dashboardYear,
            'activeUsersCount' => User::query()->where('is_active', true)->where('is_approved', true)->count(),
            'inactiveUsersCount' => User::query()->where('is_active', false)->where('is_approved', true)->count(),
            'pendingEmployeeApprovalsCount' => User::query()
                ->where('role', UserRole::EMPLOYEE->value)
                ->where('is_approved', false)
                ->count(),
            'departmentsCount' => Department::query()->count(),
            'activeDepartmentsCount' => Department::query()->where('is_active', true)->count(),
            'employeesCount' => User::query()->where('role', 'employee')->count(),
            'managersCount' => User::query()->where('role', 'manager')->count(),
            'administratorsCount' => User::query()->where('role', 'admin')->count(),
            'documentsCount' => Document::query()->count(),
            'documentsSize' => (int) Document::query()->sum('size'),
            'submissionsCount' => (int) $submissionCounts->sum(),
            'draftSubmissionsCount' => (int) $submissionCounts->get(SubmissionStatus::DRAFT->value, 0),
            'pendingSubmissionsCount' => (int) $submissionCounts->get(SubmissionStatus::SUBMITTED->value, 0),
            'approvedSubmissionsCount' => (int) $submissionCounts->get(SubmissionStatus::APPROVED->value, 0),
            'rejectedSubmissionsCount' => (int) $submissionCounts->get(SubmissionStatus::REJECTED->value, 0),
            'latestSubmissions' => $latestSubmissions,
            'latestDocuments' => $latestDocuments,
            'storageAvailable' => $this->externalStorage->isAvailable(),
        ]);
    }
}
