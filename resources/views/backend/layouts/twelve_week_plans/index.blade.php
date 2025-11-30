@extends('backend.app', ['title' => '12-Week Plans'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            {{-- Page Header --}}
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1 class="page-title mb-0">12-Week Plans</h1>
                <a href="{{ route('admin.twelve_week_plans.create') }}" class="btn btn-primary">Add New Plan</a>
            </div>

            {{-- Alerts --}}
     

            {{-- Table Card --}}
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
               <table class="table table-bordered table-striped">                                    <thead class="">
                                        <tr>
                                            <th>#</th>
                                            <th>Assigned User</th>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($plans as $plan)
                                        <tr>
                                            <td>{{ $loop->iteration + ($plans->currentPage()-1) * $plans->perPage() }}</td>
                                            <td>
                                                {{ $plan->user ? $plan->user->name . ' (' . $plan->user->email . ')' : 'N/A' }}
                                            </td>
                                            <td>{{ $plan->title }}</td>
                                            <td>{!! $plan->description !!}</td>
                                            <td>
                                                <a href="{{ route('admin.twelve_week_plans.edit', $plan->id) }}" class="btn btn-sm btn-warning me-1">Edit</a>
                                                <form action="{{ route('admin.twelve_week_plans.destroy', $plan->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No plans found.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination --}}
                            <div class="mt-3 px-3">
                                {{ $plans->links() }}
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
