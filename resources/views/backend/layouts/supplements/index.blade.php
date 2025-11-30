@extends('backend.app', ['title' => 'Supplements'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                {{-- Page Header --}}
                <div class="page-header d-flex justify-content-between align-items-center">
                    <h1 class="page-title mb-0">Supplements</h1>
                    <a href="{{ route('admin.supplements.create') }}" class="btn btn-primary">Add New</a>
                </div>

         f

                {{-- Table Card --}}
                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card border-0">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="">
                                            <tr>
                                                <th>#</th>
                                                <th>Assigned User</th>
                                                <th>Name</th>
                                                <th>Dosage / Description</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($supplements as $supplement)
                                                <tr>
                                                    <td>{{ $loop->iteration + ($supplements->currentPage() - 1) * $supplements->perPage() }}
                                                    </td>
                                                    <td>
                                                        {{ $supplement->user ? $supplement->user->name . ' (' . $supplement->user->email . ')' : 'N/A' }}
                                                    </td>
                                                    <td>{{ $supplement->name }}</td>
                                                    <td>{!! $supplement->dosage !!}</td>
                                                    <td>
                                                        <a href="{{ route('admin.supplements.edit', $supplement->id) }}"
                                                            class="btn btn-sm btn-warning me-1">Edit</a>
                                                        <form
                                                            action="{{ route('admin.supplements.destroy', $supplement->id) }}"
                                                            method="POST" class="d-inline-block"
                                                            onsubmit="return confirm('Are you sure?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn btn-sm btn-danger">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">No supplements found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Pagination --}}
                                <div class="mt-3 px-3">
                                    {{ $supplements->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- /Table Card --}}

            </div>
        </div>
    </div>
@endsection
