<?php

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Document;
use App\Models\MonthlySubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('administrators can view dashboard statistics and recent dossiers', function () {
    $this->travelTo('2026-03-15 12:00:00');

    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);
    $employee = User::factory()->create([
        'name' => 'Alice Martin',
        'department_id' => $department->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);
    User::factory()->create([
        'name' => 'Demande en attente',
        'department_id' => $department->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
        'is_approved' => false,
    ]);
    MonthlySubmission::create([
        'user_id' => $employee->id,
        'department_id' => $department->id,
        'year' => 2026,
        'month' => 3,
        'status' => SubmissionStatus::SUBMITTED,
    ]);
    $olderSubmission = MonthlySubmission::create([
        'user_id' => $employee->id,
        'department_id' => $department->id,
        'year' => 2025,
        'month' => 12,
        'status' => SubmissionStatus::SUBMITTED,
    ]);
    Document::create([
        'monthly_submission_id' => $olderSubmission->id,
        'uploaded_by' => $employee->id,
        'original_name' => 'archive-2025.pdf',
        'stored_name' => 'archive-2025.pdf',
        'disk' => 'ged_external',
        'path' => 'documents/archive-2025.pdf',
        'mime_type' => 'application/pdf',
        'size' => 4096,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewIs('admin.dashboard')
        ->assertViewHas('activeUsersCount', 2)
        ->assertViewHas('pendingEmployeeApprovalsCount', 1)
        ->assertViewHas('departmentsCount', 1)
        ->assertViewHas('pendingSubmissionsCount', 1)
        ->assertViewHas('submissionsCount', 1)
        ->assertViewHas('latestDocuments', function ($documents) {
            return $documents->count() === 1
                && $documents->first()->original_name === 'archive-2025.pdf';
        })
        ->assertSee('Alice Martin')
        ->assertSee('archive-2025.pdf')
        ->assertSee('Ajouter un utilisateur')
        ->assertSee('1 demande(s)')
        ->assertSee('Ajouter un service')
        ->assertSee('Gérer tous les dossiers');
});

test('administrators can approve pending employee accounts', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);
    $employee = User::factory()->create([
        'department_id' => $department->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => false,
        'is_approved' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('users.index', ['approval' => 'pending']))
        ->assertOk()
        ->assertSee($employee->name)
        ->assertSee('En attente de validation')
        ->assertSee(route('users.approve', $employee))
        ->assertSee('Inscriptions à valider');

    $this->patch(route('users.approve', $employee))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success', 'Compte employé validé avec succès.');

    $this->assertDatabaseHas('users', [
        'id' => $employee->id,
        'is_active' => true,
        'is_approved' => true,
    ]);
});

test('employees cannot approve pending employee accounts', function () {
    $adminLikeUser = User::factory()->create([
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);
    $employee = User::factory()->create([
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
        'is_approved' => false,
    ]);

    $this->actingAs($adminLikeUser)
        ->patch(route('users.approve', $employee))
        ->assertForbidden();

    $this->assertDatabaseHas('users', [
        'id' => $employee->id,
        'is_approved' => false,
    ]);
});

test('administrators can delete documents from the dashboard', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
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
        'month' => 3,
        'status' => SubmissionStatus::APPROVED,
    ]);
    $path = 'documents/2026/department_'.$department->id.'/user_'.$employee->id.'/month_03/submission_'.$submission->id.'/rapport.pdf';
    $document = Document::create([
        'monthly_submission_id' => $submission->id,
        'uploaded_by' => $employee->id,
        'original_name' => 'rapport.pdf',
        'stored_name' => 'rapport.pdf',
        'disk' => 'ged_external',
        'path' => $path,
        'mime_type' => 'application/pdf',
        'size' => 12,
    ]);
    Storage::fake('ged_external');
    Storage::disk('ged_external')->put($path, 'document content');

    $this->actingAs($admin)
        ->from(route('admin.dashboard'))
        ->delete(route('documents.destroy', $document))
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('success', 'Document supprimé avec succès.');

    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    Storage::disk('ged_external')->assertMissing($path);
});

test('administrators can filter all department submissions by status', function () {
    $firstDepartment = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $secondDepartment = Department::create([
        'name' => 'Informatique',
        'is_active' => true,
    ]);
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);
    $firstEmployee = User::factory()->create([
        'name' => 'Alice Martin',
        'department_id' => $firstDepartment->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);
    $secondEmployee = User::factory()->create([
        'name' => 'Bob Durant',
        'department_id' => $secondDepartment->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);

    MonthlySubmission::create([
        'user_id' => $firstEmployee->id,
        'department_id' => $firstDepartment->id,
        'year' => 2026,
        'month' => 3,
        'status' => SubmissionStatus::SUBMITTED,
    ]);
    MonthlySubmission::create([
        'user_id' => $secondEmployee->id,
        'department_id' => $secondDepartment->id,
        'year' => 2026,
        'month' => 3,
        'status' => SubmissionStatus::DRAFT,
    ]);

    $this->actingAs($admin)
        ->get(route('submissions.index', [
            'year' => 2026,
            'status' => SubmissionStatus::SUBMITTED->value,
        ]))
        ->assertOk()
        ->assertViewHas('submissions', function ($submissions) use ($firstEmployee) {
            return $submissions->count() === 1
                && $submissions->first()->user->is($firstEmployee);
        });
});

test('administrators can approve transmitted submissions', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
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
        'month' => 3,
        'status' => SubmissionStatus::SUBMITTED,
    ]);

    $this->actingAs($admin)
        ->post(route('submissions.approve', $submission))
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHas('success', 'Dossier approuvé avec succès.');

    $this->assertDatabaseHas('monthly_submissions', [
        'id' => $submission->id,
        'status' => SubmissionStatus::APPROVED->value,
    ]);
});

test('administrators can reject transmitted submissions', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
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
        'month' => 3,
        'status' => SubmissionStatus::SUBMITTED,
    ]);

    $this->actingAs($admin)
        ->post(route('submissions.reject', $submission))
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHas('success', 'Dossier refusé.');

    $this->assertDatabaseHas('monthly_submissions', [
        'id' => $submission->id,
        'status' => SubmissionStatus::REJECTED->value,
    ]);
});

test('managers cannot review a submission from another department', function () {
    $managerDepartment = Department::create([
        'name' => 'Informatique',
        'is_active' => true,
    ]);
    $otherDepartment = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $manager = User::factory()->create([
        'department_id' => $managerDepartment->id,
        'role' => UserRole::MANAGER,
        'is_active' => true,
    ]);
    $employee = User::factory()->create([
        'department_id' => $otherDepartment->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);
    $submission = MonthlySubmission::create([
        'user_id' => $employee->id,
        'department_id' => $otherDepartment->id,
        'year' => 2026,
        'month' => 3,
        'status' => SubmissionStatus::SUBMITTED,
    ]);

    $this->actingAs($manager)
        ->post(route('submissions.approve', $submission))
        ->assertForbidden();

    $this->assertDatabaseHas('monthly_submissions', [
        'id' => $submission->id,
        'status' => SubmissionStatus::SUBMITTED->value,
    ]);
});

test('employees cannot access the administrator dashboard', function () {
    $employee = User::factory()->create([
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);

    $this->actingAs($employee)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
