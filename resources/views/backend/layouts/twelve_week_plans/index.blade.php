@extends('backend.app', ['title' => '12-Week Plans'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <div class="page-header">
                <h1 class="page-title">12-Week Plans</h1>
                <div class="ms-auto pageheader-btn">
                    <a href="{{ route('admin.twelve_week_plans.create') }}" class="btn btn-primary">Add New Plan</a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($plans as $plan)
                                        <tr>
                                            <td>{{ $loop->iteration + ($plans->currentPage() - 1) * $plans->perPage() }}</td>
                                            <td>{{ $plan->title }}</td>
                                            <td>{!! nl2br(e($plan->description)) !!}</td>
                                            <td>
                                                <a href="{{ route('admin.twelve_week_plans.edit', $plan->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                                <form action="{{ route('admin.twelve_week_plans.destroy', $plan->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No plans found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            {{ $plans->links() }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
