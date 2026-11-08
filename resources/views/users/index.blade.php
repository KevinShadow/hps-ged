@extends('layouts.app')

@section('title', 'Utilisateurs | HPS GED')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold text-uppercase small mb-1">Administration</p>
            <h1 class="h2 mb-0">Gestion des utilisateurs</h1>
        </div>
        <a class="btn btn-primary" href="{{ route('users.create') }}">
            Ajouter un utilisateur
        </a>
    </div>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="department_id">Filtrer par service</label>
                    <select class="form-select" name="department_id" id="department_id">
                        <option value="">Tous les services</option>
                        @foreach($departments as $department)
                            <option
                                value="{{ $department->id }}"
                                @selected($departmentId == $department->id)
                            >
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="approval">État des demandes</label>
                    <select class="form-select" name="approval" id="approval">
                        <option value="all" @selected($approvalStatus === 'all')>Tous les utilisateurs</option>
                        <option value="pending" @selected($approvalStatus === 'pending')>Inscriptions à valider</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary" type="submit">Filtrer</button>
                    <a class="btn btn-link text-secondary" href="{{ route('users.index') }}">Réinitialiser</a>
                </div>
            </form>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Service</th>
                        <th scope="col">Rôle</th>
                        <th scope="col">Statut</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->department?->name ?? 'Non affecté' }}</td>
                            <td>
                                {{
                                    [
                                        'admin' => 'Administrateur',
                                        'manager' => 'Responsable',
                                        'employee' => 'Employé',
                                    ][$user->role->value] ?? $user->role->value
                                }}
                            </td>
                            <td>
                                @if(!$user->is_approved)
                                    <span class="badge text-bg-warning">En attente de validation</span>
                                @else
                                    <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $user->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('users.edit', $user) }}">
                                        Modifier
                                    </a>
                                    @if(!$user->is_approved)
                                        @can('approve', $user)
                                            <form method="POST" action="{{ route('users.approve', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-success" type="submit">
                                                    Approuver
                                                </button>
                                            </form>
                                        @endcan
                                    @else
                                        <form
                                            method="POST"
                                            action="{{ route($user->is_active ? 'users.deactivate' : 'users.activate', $user) }}"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                type="submit"
                                            >
                                                {{ $user->is_active ? 'Désactiver' : 'Activer' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-5">
                                Aucun utilisateur trouvé pour ce filtre.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $users->withQueryString()->links() }}
            </div>
        @endif
    </section>
@endsection