<?php

namespace App\Http\Controllers;

use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService
    ) {}

    /**
     * Liste des services.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Department::class);

        $departments = $this->departmentService
            ->getPaginatedDepartments(15);

        return view('departments.index', compact('departments'));
    }

    /**
     * Formulaire de création.
     */
    public function create(): View
    {
        Gate::authorize('create', Department::class);

        return view('departments.create');
    }

    /**
     * Enregistrement d'un service.
     */
    public function store(
        StoreDepartmentRequest $request
    ): RedirectResponse {
        $this->departmentService->createDepartment(
            $request->validated()
        );

        return redirect()
            ->route('departments.index')
            ->with('success', 'Service créé avec succès.');
    }

    /**
     * Formulaire de modification.
     */
    public function edit(Department $department): View
    {
        Gate::authorize('update', $department);

        return view('departments.edit', compact('department'));
    }

    /**
     * Modification d'un service.
     */
    public function update(
        UpdateDepartmentRequest $request,
        Department $department
    ): RedirectResponse {
        Gate::authorize('update', $department);

        $this->departmentService->updateDepartment(
            $department->id,
            $request->validated()
        );

        return redirect()
            ->route('departments.index')
            ->with('success', 'Service modifié avec succès.');
    }

    /**
     * Désactivation d'un service.
     */
    public function deactivate(Department $department): RedirectResponse
    {
        Gate::authorize('update', $department);

        $this->departmentService->deactivateDepartment(
            $department->id
        );

        return redirect()
            ->route('departments.index')
            ->with('success', 'Service désactivé avec succès.');
    }
}
