<?php

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests can view the login form', function () {
    $this->get('/login')
        ->assertOk()
        ->assertViewIs('auth.login');
});

test('guests can view the employee registration form', function () {
    Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);

    $this->get(route('register'))
        ->assertOk()
        ->assertViewIs('auth.register')
        ->assertSee('Inscription employé')
        ->assertSee('Comptabilité');
});

test('guests can navigate from the home page to login', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertViewIs('welcome')
        ->assertSee('Se connecter pour accéder à mes dossiers')
        ->assertSee('Créer un compte employé')
        ->assertSee(route('login'));
});

test('guests can register as pending employees without assigning privileged fields', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);

    $this->post(route('register.store'), [
        'name' => 'Alex Martin',
        'email' => 'alex@example.com',
        'department_id' => $department->id,
        'password' => 'A-strong-password-123',
        'password_confirmation' => 'A-strong-password-123',
        'role' => UserRole::ADMIN->value,
        'is_active' => false,
        'is_approved' => true,
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Votre demande a été envoyée. Vous pourrez vous connecter après validation par un administrateur.');

    $this->assertDatabaseHas('users', [
        'name' => 'Alex Martin',
        'email' => 'alex@example.com',
        'department_id' => $department->id,
        'role' => UserRole::EMPLOYEE->value,
        'is_active' => true,
        'is_approved' => false,
    ]);
    $this->assertGuest();
});

test('guests cannot register for an inactive department', function () {
    $department = Department::create([
        'name' => 'Archives',
        'is_active' => false,
    ]);

    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Alex Martin',
            'email' => 'alex@example.com',
            'department_id' => $department->id,
            'password' => 'A-strong-password-123',
            'password_confirmation' => 'A-strong-password-123',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('department_id');

    $this->assertDatabaseMissing('users', ['email' => 'alex@example.com']);
});

test('guests cannot register with an email already in use', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    User::factory()->create([
        'email' => 'alex@example.com',
    ]);

    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Alex Martin',
            'email' => 'alex@example.com',
            'department_id' => $department->id,
            'password' => 'A-strong-password-123',
            'password_confirmation' => 'A-strong-password-123',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('email');

    $this->assertDatabaseCount('users', 1);
});

test('guests are redirected to login before viewing protected pages', function () {
    $this->get(route('submissions.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users with a disabled account are signed out', function () {
    $user = User::factory()->create([
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->get(route('submissions.index'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'Votre compte est désactivé.',
        ]);

    $this->assertGuest();
});

test('active users can sign in and are redirected to their intended page', function () {
    $user = User::factory()->create([
        'password' => 'password',
        'is_active' => true,
    ]);

    $response = $this->get(route('submissions.index'));

    $response->assertRedirect(route('login'));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('submissions.index'));

    $this->assertAuthenticatedAs($user);
});

test('invalid credentials are rejected without authenticating the user', function () {
    $user = User::factory()->create([
        'password' => 'password',
        'is_active' => true,
    ]);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'Identifiants invalides ou compte désactivé.',
        ]);

    $this->assertGuest();
});

test('inactive users cannot sign in', function () {
    $user = User::factory()->create([
        'password' => 'password',
        'is_active' => false,
    ]);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'Identifiants invalides ou compte désactivé.',
        ]);

    $this->assertGuest();
});

test('employees cannot sign in before administrator approval', function () {
    $user = User::factory()->create([
        'password' => 'password',
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
        'is_approved' => false,
    ]);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'Identifiants invalides ou compte désactivé.',
        ]);

    $this->assertGuest();
});

test('authenticated users can sign out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('administrators can sign out from the shared navigation', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Déconnexion')
        ->assertSee(route('logout'));

    $this->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
