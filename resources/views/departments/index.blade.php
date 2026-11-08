@extends('layouts.app')

@section('title', 'Services | HPS GED')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold text-uppercase small mb-1">Administration</p>
            <h1 class="h2 mb-0">Services</h1>
        </div>
        <a class="btn btn-primary" href="{{ route('departments.create') }}">
            Ajouter un service
        </a>
    </div>

    <section class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Service</th>
                        <th scope="col">Description</th>
                        <th scope="col">Statut</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td class="fw-semibold">{{ $department->name }}</td>
                            <td>{{ $department->description ?: 'Aucune description' }}</td>
                            <td>
                                <span class="badge {{ $department->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $department->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <a
                                        class="btn btn-sm btn-outline-primary"
                                        href="{{ route('departments.edit', $department) }}"
                                    >
                                        Modifier
                                    </a>
                                    @if($department->is_active)
                                        <form method="POST" action="{{ route('departments.deactivate', $department) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                                Désactiver
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-body-secondary py-5">
                                Aucun service enregistré.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($departments->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $departments->links() }}
            </div>
        @endif
    </section>
@endsection