<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Http\Requests\Submission\StoreSubmissionRequest;
use App\Models\Department;
use App\Models\MonthlySubmission;
use App\Models\User;
use App\Services\MonthlySubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MonthlySubmissionController extends Controller
{
    public function __construct(
        protected MonthlySubmissionService $submissionService
    ) {}

    /**
     * Liste des soumissions.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', MonthlySubmission::class);

        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status' => ['nullable', Rule::enum(SubmissionStatus::class)],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $year = (int) ($filters['year'] ?? now()->year);
        $month = isset($filters['month']) ? (int) $filters['month'] : null;
        $status = isset($filters['status'])
            ? SubmissionStatus::from($filters['status'])
            : null;
        $departmentId = isset($filters['department_id'])
            ? (int) $filters['department_id']
            : null;
        $departments = collect();

        if ($user->isAdmin()) {
            $departments = Department::query()
                ->orderBy('name')
                ->get();

            $submissions = $this->submissionService->getAllSubmissions(
                $year,
                $month,
                $departmentId,
                $status
            );
        } elseif ($user->isManager() && $user->department_id !== null) {
            $submissions = $this->submissionService->getDepartmentSubmissions(
                $user->department_id,
                $year,
                $month,
                $status
            );
        } elseif ($user->isEmployee()) {
            $submission = $month !== null
                ? $this->submissionService->getUserSubmission(
                    $user->id,
                    $year,
                    $month
                )
                : null;
            $submissions = collect($submission ? [$submission] : []);
            $submissions = collect($submission ? [$submission] : []);
        } else {
            $submissions = collect();
        }

        return view('submissions.index', compact(
            'submissions',
            'year',
            'month',
            'status',
            'departments',
            'departmentId'
        ));
    }

    /**
     * Formulaire de création.
     */
    public function create(): View
    {
        Gate::authorize('create', MonthlySubmission::class);

        return view('submissions.create');
    }

    /**
     * Création d'une soumission.
     */
    public function store(
        StoreSubmissionRequest $request
    ): RedirectResponse {

        Gate::authorize('create', MonthlySubmission::class);

        $user = Auth::user();

        $submission = $this->submissionService->createSubmission(
            $user->id,
            $user->department_id,
            (int) $request->validated('year'),
            (int) $request->validated('month')
        );

        return redirect()
            ->route('submissions.show', $submission->id)
            ->with(
                'success',
                $submission->wasRecentlyCreated
                    ? 'Dossier mensuel créé avec succès.'
                    : 'Dossier du mois ouvert. Vous pouvez y ajouter des documents.'
            );
    }

    /**
     * Détail d'une soumission.
     */
    public function show(
        MonthlySubmission $submission
    ): View {

        Gate::authorize('view', $submission);

        $submission = $this->submissionService
            ->getSubmission($submission->id);

        abort_if(! $submission, 404);

        return view('submissions.show', compact('submission'));
    }

    /**
     * Transmission d'une soumission.
     */
    public function submit(
        MonthlySubmission $submission
    ): RedirectResponse {

        Gate::authorize('submit', $submission);

        $result = $this->submissionService
            ->submitSubmission($submission->id);

        if (! $result) {
            return back()->withErrors([
                'submission' => 'Impossible de transmettre ce dossier.',
            ]);
        }

        return redirect()
            ->route('submissions.show', $submission->id)
            ->with('success', 'Dossier transmis avec succès.');
    }

    public function approve(
        MonthlySubmission $submission
    ): RedirectResponse {
        Gate::authorize('approve', $submission);

        $this->submissionService->approveSubmission($submission->id);

        return redirect()
            ->route('submissions.show', $submission)
            ->with('success', 'Dossier approuvé avec succès.');
    }

    public function reject(
        MonthlySubmission $submission
    ): RedirectResponse {
        Gate::authorize('reject', $submission);

        $this->submissionService->rejectSubmission($submission->id);

        return redirect()
            ->route('submissions.show', $submission)
            ->with('success', 'Dossier refusé.');
    }
}
