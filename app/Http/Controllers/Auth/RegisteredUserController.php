<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\Department;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function create(): View
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('auth.register', compact('departments'));
    }

    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $this->userService->registerEmployee($request->validated());

        return redirect()
            ->route('login')
            ->with('status', 'Votre demande a été envoyée. Vous pourrez vous connecter après validation par un administrateur.');
    }
}
