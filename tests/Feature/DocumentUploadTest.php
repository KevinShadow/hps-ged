<?php

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Document;
use App\Models\MonthlySubmission;
use App\Models\User;
use App\Services\ExternalStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('employees upload documents into the dossier storage hierarchy', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $employee = User::factory()->create([
        'name' => 'Jean Dupont',
        'department_id' => $department->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);
    $submission = MonthlySubmission::create([
        'user_id' => $employee->id,
        'department_id' => $department->id,
        'year' => 2026,
        'month' => 3,
        'status' => SubmissionStatus::DRAFT,
    ]);

    config([
        'filesystems.disks.ged_external.root' => storage_path('framework/testing/ged-documents-auto-created'),
    ]);
    Storage::fake('ged_external');

    $this->actingAs($employee)
        ->from(route('submissions.show', $submission))
        ->post(route('documents.store', $submission), [
            'monthly_submission_id' => $submission->id,
            'files' => [
                UploadedFile::fake()->create('rapport.pdf', 12, 'application/pdf'),
            ],
        ])
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHas('success', 'Document ajouté avec succès.')
        ->assertSessionHas('upload_result', [
            'added' => ['rapport.pdf'],
            'duplicates' => [],
        ]);

    $document = Document::query()->sole();
    $expectedDirectory = sprintf(
        'documents/2026/comptabilite/jean-dupont_%d/month_03/submission_%d',
        $employee->id,
        $submission->id
    );

    expect($document->path)->toStartWith($expectedDirectory.'/');
    expect($document->stored_name)->toEndWith('.pdf');
    expect($document->original_name)->toBe('rapport.pdf');

    Storage::disk('ged_external')->assertExists($document->path);

    $this->actingAs($employee)
        ->get(route('documents.download', $document))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=rapport.pdf');
});

test('employees upload multiple files and preserve selected folder paths inside the month', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $employee = User::factory()->create([
        'name' => 'Jean Dupont',
        'department_id' => $department->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);
    $submission = MonthlySubmission::create([
        'user_id' => $employee->id,
        'department_id' => $department->id,
        'year' => 2026,
        'month' => 3,
        'status' => SubmissionStatus::DRAFT,
    ]);
    config([
        'filesystems.disks.ged_external.root' => storage_path('framework/testing/ged-documents-multiple'),
    ]);
    Storage::fake('ged_external');

    $this->actingAs($employee)
        ->post(route('documents.store', $submission), [
            'monthly_submission_id' => $submission->id,
            'files' => [
                UploadedFile::fake()->create('rapport.pdf', 12, 'application/pdf'),
                UploadedFile::fake()->create('contrat.docx', 12, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ],
            'relative_paths' => [
                'Documents à classer/Factures/rapport.pdf',
                'Documents à classer/Contrats/contrat.docx',
            ],
        ], [
            'Accept' => 'application/json',
        ])
        ->assertOk()
        ->assertJson([
            'uploaded' => 2,
            'message' => '2 documents ajoutés avec succès.',
            'added' => [
                'documents-a-classer/factures/rapport.pdf',
                'documents-a-classer/contrats/contrat.docx',
            ],
        ]);

    $documents = Document::query()->orderBy('original_name')->get();

    expect($documents)->toHaveCount(2);
    expect($documents[0]->original_name)->toBe('documents-a-classer/contrats/contrat.docx');
    expect($documents[1]->original_name)->toBe('documents-a-classer/factures/rapport.pdf');
    expect($documents[0]->path)->toContain('/contrats/');
    expect($documents[1]->path)->toContain('/factures/');
    expect($documents[0]->path)->toContain('/documents-a-classer/');
    expect($documents[1]->path)->toContain('/documents-a-classer/');

    foreach ($documents as $document) {
        Storage::disk('ged_external')->assertExists($document->path);
    }
});

test('employees cannot upload files with a traversal path', function () {
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
        'month' => 3,
        'status' => SubmissionStatus::DRAFT,
    ]);
    config([
        'filesystems.disks.ged_external.root' => storage_path('framework/testing/ged-documents-invalid-path'),
    ]);
    Storage::fake('ged_external');

    $this->actingAs($employee)
        ->from(route('submissions.show', $submission))
        ->post(route('documents.store', $submission), [
            'monthly_submission_id' => $submission->id,
            'files' => [
                UploadedFile::fake()->create('rapport.pdf', 12, 'application/pdf'),
            ],
            'relative_paths' => ['../../../rapport.pdf'],
        ])
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHasErrors('relative_paths.0');

    $this->assertDatabaseCount('documents', 0);
    expect(Storage::disk('ged_external')->allFiles())->toBe([]);
});

test('employees skip same-named files already present in the monthly dossier', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $employee = User::factory()->create([
        'name' => 'Jean Dupont',
        'department_id' => $department->id,
        'role' => UserRole::EMPLOYEE,
        'is_active' => true,
    ]);
    $submission = MonthlySubmission::create([
        'user_id' => $employee->id,
        'department_id' => $department->id,
        'year' => 2026,
        'month' => 3,
        'status' => SubmissionStatus::DRAFT,
    ]);

    config([
        'filesystems.disks.ged_external.root' => storage_path('framework/testing/ged-documents-duplicates'),
    ]);
    Storage::fake('ged_external');

    $this->actingAs($employee)
        ->post(route('documents.store', $submission), [
            'monthly_submission_id' => $submission->id,
            'files' => [
                UploadedFile::fake()->create('rapport.pdf', 12, 'application/pdf'),
                UploadedFile::fake()->create('rapport.pdf', 12, 'application/pdf'),
            ],
            'relative_paths' => [
                'Archives 2026/rapport.pdf',
                'Autres documents/rapport.pdf',
            ],
        ], [
            'Accept' => 'application/json',
        ])
        ->assertOk()
        ->assertJson([
            'uploaded' => 1,
            'added' => ['archives-2026/rapport.pdf'],
            'duplicates' => ['autres-documents/rapport.pdf'],
        ]);

    $this->post(route('documents.store', $submission), [
        'monthly_submission_id' => $submission->id,
        'files' => [
            UploadedFile::fake()->create('RAPPORT.pdf', 12, 'application/pdf'),
        ],
        'relative_paths' => ['Nouvelle archive/RAPPORT.pdf'],
    ], [
        'Accept' => 'application/json',
    ])
        ->assertOk()
        ->assertJson([
            'uploaded' => 0,
            'added' => [],
            'duplicates' => ['nouvelle-archive/RAPPORT.pdf'],
        ]);

    $this->from(route('submissions.show', $submission))
        ->post(route('documents.store', $submission), [
            'monthly_submission_id' => $submission->id,
            'files' => [
                UploadedFile::fake()->create('rapport.pdf', 12, 'application/pdf'),
            ],
        ])
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHas('upload_result', [
            'message' => 'Aucun document ajouté : les fichiers sélectionnés existent déjà dans ce dossier mensuel. 1 doublon ignoré.',
            'added' => [],
            'duplicates' => ['rapport.pdf'],
        ]);

    $documents = Document::query()->get();

    expect($documents)->toHaveCount(1);
    expect($documents->sole()->original_name)->toBe('archives-2026/rapport.pdf');
    expect(Storage::disk('ged_external')->allFiles())->toHaveCount(1);

    foreach ($documents as $document) {
        expect($document->path)->toStartWith(
            sprintf(
                'documents/2026/comptabilite/jean-dupont_%d/month_03/submission_%d/',
                $employee->id,
                $submission->id
            )
        );
        Storage::disk('ged_external')->assertExists($document->path);
    }
});

test('employees can add documents to a transmitted dossier and reopen it in draft', function () {
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
        'month' => 3,
        'status' => SubmissionStatus::SUBMITTED,
        'submitted_at' => now()->subDay(),
    ]);
    $existingDocument = Document::create([
        'monthly_submission_id' => $submission->id,
        'uploaded_by' => $employee->id,
        'original_name' => 'ancien-rapport.pdf',
        'stored_name' => 'ancien-rapport.pdf',
        'disk' => 'ged_external',
        'path' => 'documents/ancien-rapport.pdf',
        'mime_type' => 'application/pdf',
        'size' => 12,
    ]);
    Storage::fake('ged_external');
    Storage::disk('ged_external')->put($existingDocument->path, 'existing document');

    $this->actingAs($employee)
        ->post(route('documents.store', $submission), [
            'monthly_submission_id' => $submission->id,
            'files' => [
                UploadedFile::fake()->create('rapport-complementaire.pdf', 12, 'application/pdf'),
            ],
        ], [
            'Accept' => 'application/json',
        ])
        ->assertOk()
        ->assertJson([
            'uploaded' => 1,
            'reopened' => true,
        ]);

    $this->assertDatabaseHas('monthly_submissions', [
        'id' => $submission->id,
        'status' => SubmissionStatus::DRAFT->value,
        'submitted_at' => null,
    ]);
    $this->assertDatabaseCount('documents', 2);
    Storage::disk('ged_external')->assertExists($existingDocument->path);

    $this->get(route('submissions.show', $submission))
        ->assertOk()
        ->assertSee('Ajouter des documents')
        ->assertSee('Transmettre le dossier')
        ->assertSee('Résultat de l’ajout')
        ->assertSee('Fichiers ajoutés')
        ->assertSee('Fichiers ignorés (nom déjà présent)')
        ->assertSee('data-confirm')
        ->assertDontSee('onsubmit=');

    $this->post(route('submissions.submit', $submission))
        ->assertRedirect(route('submissions.show', $submission));

    $this->post(route('documents.store', $submission), [
        'monthly_submission_id' => $submission->id,
        'files' => [
            UploadedFile::fake()->create('ancien-rapport.pdf', 12, 'application/pdf'),
        ],
    ], [
        'Accept' => 'application/json',
    ])
        ->assertOk()
        ->assertJson([
            'uploaded' => 0,
            'duplicates' => ['ancien-rapport.pdf'],
            'reopened' => false,
        ]);

    $this->assertDatabaseHas('monthly_submissions', [
        'id' => $submission->id,
        'status' => SubmissionStatus::SUBMITTED->value,
    ]);
});

test('employees can add documents to an approved dossier and reopen it in draft', function () {
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
        'month' => 3,
        'status' => SubmissionStatus::APPROVED,
    ]);
    Storage::fake('ged_external');

    $this->actingAs($employee)
        ->get(route('submissions.show', $submission))
        ->assertOk()
        ->assertSee('Ajouter des documents')
        ->assertSee('Ce dossier a déjà été transmis ou approuvé.');

    $this->post(route('documents.store', $submission), [
        'monthly_submission_id' => $submission->id,
        'files' => [
            UploadedFile::fake()->create('rapport.pdf', 12, 'application/pdf'),
        ],
    ], [
        'Accept' => 'application/json',
    ])
        ->assertOk()
        ->assertJson([
            'uploaded' => 1,
            'reopened' => true,
        ]);

    $this->assertDatabaseHas('monthly_submissions', [
        'id' => $submission->id,
        'status' => SubmissionStatus::DRAFT->value,
    ]);
    $this->assertDatabaseCount('documents', 1);
});

test('ajax uploads return a clear error when external storage is unavailable', function (bool $storageThrows, string $message) {
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
        'month' => 3,
        'status' => SubmissionStatus::DRAFT,
    ]);
    $storage = Mockery::mock(ExternalStorageService::class);

    if ($storageThrows) {
        $storage->shouldReceive('ensureAvailable')
            ->once()
            ->andThrow(new RuntimeException('Storage unavailable.'));
    } else {
        $storage->shouldReceive('ensureAvailable')
            ->once()
            ->andReturn(false);
    }

    $this->app->instance(ExternalStorageService::class, $storage);

    $this->actingAs($employee)
        ->post(route('documents.store', $submission), [
            'monthly_submission_id' => $submission->id,
            'files' => [
                UploadedFile::fake()->create('rapport.pdf', 12, 'application/pdf'),
            ],
        ], [
            'Accept' => 'application/json',
        ])
        ->assertStatus(503)
        ->assertJson(['message' => $message]);

    $this->assertDatabaseCount('documents', 0);
})->with([
    'storage is not writable' => [
        false,
        'Le stockage externe est indisponible ou inaccessible en écriture.',
    ],
    'storage check throws' => [
        true,
        'Le stockage externe est temporairement indisponible.',
    ],
]);

test('employees can update and resubmit a rejected dossier', function () {
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
        'month' => 3,
        'status' => SubmissionStatus::REJECTED,
    ]);

    config([
        'filesystems.disks.ged_external.root' => storage_path('framework/testing/ged-documents-rejected'),
    ]);
    Storage::fake('ged_external');

    $this->actingAs($employee)
        ->from(route('submissions.show', $submission))
        ->post(route('documents.store', $submission), [
            'monthly_submission_id' => $submission->id,
            'files' => [
                UploadedFile::fake()->create('rapport-corrige.pdf', 12, 'application/pdf'),
            ],
        ])
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHas('success', 'Document ajouté avec succès.');

    $this->post(route('submissions.submit', $submission))
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHas('success', 'Dossier transmis avec succès.');

    $this->assertDatabaseHas('monthly_submissions', [
        'id' => $submission->id,
        'status' => SubmissionStatus::SUBMITTED->value,
    ]);
    $this->assertDatabaseCount('documents', 1);
});

test('employees can delete documents from rejected dossiers', function () {
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
        'month' => 3,
        'status' => SubmissionStatus::REJECTED,
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

    $this->actingAs($employee)
        ->from(route('submissions.show', $submission))
        ->delete(route('documents.destroy', $document))
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHas('success', 'Document supprimé avec succès.');

    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    Storage::disk('ged_external')->assertMissing($path);
});

test('employees see an error when the physical document cannot be deleted', function () {
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
        'month' => 3,
        'status' => SubmissionStatus::DRAFT,
    ]);
    $document = Document::create([
        'monthly_submission_id' => $submission->id,
        'uploaded_by' => $employee->id,
        'original_name' => 'rapport.pdf',
        'stored_name' => 'rapport.pdf',
        'disk' => 'ged_external',
        'path' => 'documents/rapport.pdf',
        'mime_type' => 'application/pdf',
        'size' => 12,
    ]);
    Storage::fake('ged_external');

    $this->actingAs($employee)
        ->from(route('submissions.show', $submission))
        ->delete(route('documents.destroy', $document))
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHasErrors([
            'document' => 'Une erreur est survenue pendant la suppression du document.',
        ]);

    $this->assertDatabaseHas('documents', ['id' => $document->id]);
});

test('ged storage creates its configured directory when it is missing', function () {
    $root = storage_path('framework/testing/ged-storage-auto-create');
    File::deleteDirectory($root);
    config(['filesystems.disks.ged_external.root' => $root]);

    $storage = new ExternalStorageService;

    expect($storage->isAvailable())->toBeTrue();
    expect($storage->ensureAvailable())->toBeTrue();
    expect(File::isDirectory($root))->toBeTrue();

    File::deleteDirectory($root);
});

test('employees cannot upload unsupported document types', function () {
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
        'month' => 3,
        'status' => SubmissionStatus::DRAFT,
    ]);

    $this->actingAs($employee)
        ->from(route('submissions.show', $submission))
        ->post(route('documents.store', $submission), [
            'monthly_submission_id' => $submission->id,
            'files' => [
                UploadedFile::fake()->create('script.exe', 12, 'application/x-msdownload'),
            ],
        ])
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHasErrors([
            'files.0' => 'Formats autorisés : PDF, DOC, DOCX, XLS et XLSX.',
        ]);

    $this->assertDatabaseCount('documents', 0);
});

test('managers cannot upload documents to an employee dossier', function () {
    $department = Department::create([
        'name' => 'Comptabilité',
        'is_active' => true,
    ]);
    $manager = User::factory()->create([
        'department_id' => $department->id,
        'role' => UserRole::MANAGER,
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
        'status' => SubmissionStatus::DRAFT,
    ]);

    $this->actingAs($manager)
        ->post(route('documents.store', $submission), [
            'monthly_submission_id' => $submission->id,
            'files' => [
                UploadedFile::fake()->create('rapport.pdf', 12, 'application/pdf'),
            ],
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('documents', 0);
});
