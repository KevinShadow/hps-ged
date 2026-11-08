<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Liste des utilisateurs.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $departmentId = $request->integer('department_id');
        $approvalStatus = $request->query('approval') === 'pending'
            ? 'pending'
            : 'all';

        if ($approvalStatus === 'pending') {
            $users = $this->userService->getPendingEmployees($departmentId ?: null);
        } else {
            $users = $departmentId
                ? $this->userService->getUsersByDepartment($departmentId)
                : $this->userService->getPaginatedUsers();
        }

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('users.index', compact(
            'users',
            'departments',
            'departmentId',
            'approvalStatus'
        ));
    }

    /**
     * Formulaire de création.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('users.create', compact('departments'));
    }

    /**
     * Enregistrement.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->createUser($request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Formulaire de modification.
     */
    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('users.edit', compact('user', 'departments'));
    }

    /**
     * Modification.
     */
    public function update(
        UpdateUserRequest $request,
        User $user
    ): RedirectResponse {
        Gate::authorize('update', $user);

        $this->userService->updateUser(
            $user->id,
            $request->validated()
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur modifié avec succès.');
    }

    /**
     * Désactivation.
     */
    public function deactivate(User $user): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        $this->userService->deactivateUser($user->id);

        return redirect()
            ->route('users.index')
            ->with('success', 'Compte désactivé avec succès.');
    }

    /**
     * Activation.
     */
    public function activate(User $user): RedirectResponse
    {
        Gate::authorize('activate', $user);

        $this->userService->activateUser($user->id);

        return redirect()
            ->route('users.index')
            ->with('success', 'Compte activé avec succès.');
    }

    public function approve(User $user): RedirectResponse
    {
        Gate::authorize('approve', $user);

        $this->userService->approveEmployee($user->id);

        return redirect()
            ->route('users.index')
            ->with('success', 'Compte employé validé avec succès.');
    }
}
