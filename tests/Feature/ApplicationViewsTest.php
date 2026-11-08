<?php

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\MonthlySubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can render user and department screens', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);
    $targetUser = User::factory()->create([
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);
    $department = Department::create([
        'name' => 'Finance',
        'description' => 'Gestion financière',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    $pages = [
        [route('users.index'), 'users.index'],
        [route('users.create'), 'users.create'],
        [route('users.edit', $targetUser), 'users.edit'],
        [route('departments.index'), 'departments.index'],
        [route('departments.create'), 'departments.create'],
        [route('departments.edit', $department), 'departments.edit'],
    ];

    foreach ($pages as [$url, $view]) {
        $this->get($url)
            ->assertOk()
            ->assertViewIs($view);
    }

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Tableau de bord')
        ->assertSee('Déconnexion')
        ->assertSee(route('logout'))
        ->assertSee(route('admin.dashboard'))
        ->assertSee(route('users.index'))
        ->assertSee(route('departments.index'));
});

test('employees can navigate from the home page to their dossiers', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $employee = User::factory()->create([
        'department_id' => $department->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);

    $this->actingAs($employee)
        ->get(route('home'))
        ->assertOk()
        ->assertViewIs('welcome')
        ->assertSee('Accéder aux dossiers mensuels')
        ->assertSee(route('submissions.index'))
        ->assertSee('Nouvelle sauvegarde')
        ->assertSee(route('submissions.create'))
        ->assertSee('Déconnexion')
        ->assertSee(route('logout'))
        ->assertDontSee(route('admin.dashboard'));
});

test('employees can render their monthly submission screens', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $employee = User::factory()->create([
        'department_id' => $department->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);
    $submission = MonthlySubmission::create([
        'user_id' => $employee->id,
        'department_id' => $department->id,
        'year' => 2026,
        'month' => 11,
        'status' => SubmissionStatus::DRAFT,
    ]);

    $this->actingAs($employee);

    $this->get(route('submissions.index', ['month' => 11, 'year' => 2026]))
        ->assertOk()
        ->assertViewIs('submissions.index')
        ->assertSee('Novembre')
        ->assertSee('2026');

    $this->get(route('submissions.create'))
        ->assertOk()
        ->assertViewIs('submissions.create');

    $this->get(route('submissions.show', $submission))
        ->assertOk()
        ->assertViewIs('submissions.show')
        ->assertSee('Comptabilité');
});

test('employees reopen the existing dossier when selecting the same month again', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $employee = User::factory()->create([
        'department_id' => $department->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);
    $submission = MonthlySubmission::create([
        'user_id' => $employee->id,
        'department_id' => $department->id,
        'year' => 2026,
        'month' => 11,
        'status' => SubmissionStatus::DRAFT,
    ]);

    $this->actingAs($employee)
        ->post(route('submissions.store'), [
            'year' => 2026,
            'month' => 11,
        ])
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHas('success', 'Dossier du mois ouvert. Vous pouvez y ajouter des documents.');

    $this->assertDatabaseCount('monthly_submissions', 1);
});

test('managers can render the department submission list', function () {
    $department = Department::create([
        'name' => 'Informatique',
        'is_active' => true,
    ]);
    $employee = User::factory()->create([
        'name' => 'Alice Martin',
        'department_id' => $department->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);
    $manager = User::factory()->create([
        'department_id' => $department->id,
        'role' => UserRole::MANAGER,
        'is_active' => true,
    ]);
    MonthlySubmission::create([
        'user_id' => $employee->id,
        'department_id' => $department->id,
        'year' => 2026,
        'month' => 11,
        'status' => SubmissionStatus::SUBMITTED,
    ]);

    $this->actingAs($manager)
        ->get(route('submissions.index', ['year' => 2026]))
        ->assertOk()
        ->assertViewIs('submissions.index')
        ->assertSee('Alice Martin')
        ->assertSee('Novembre')
        ->assertSee('Transmis');
});
