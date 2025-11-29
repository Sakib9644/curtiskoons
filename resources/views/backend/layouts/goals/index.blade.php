@extends('backend.app', ['title' => 'Health Goals'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <div class="page-header">
                <h1 class="page-title">Health Goals</h1>
                <div class="ms-auto pageheader-btn">
                    <a href="{{ route('admin.health_goals.create') }}" class="btn btn-primary">Create New Goal</a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">


                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Health Goals List</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Goal</th>
                                        <th>Methods</th>
                                        <th>Timeline (years)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($healthGoals as $goal)
                                        <tr>
                                            <td>{{ $loop->iteration + ($healthGoals->currentPage()-1)*$healthGoals->perPage() }}</td>
                                            <td>{{ $goal->goal }}</td>
                                            <td>{!! $goal->methods !!}</td>
                                            <td>{{ $goal->timeline_years }}</td>
                                            <td>
                                                <a href="{{ route('admin.health_goals.edit', $goal->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                                <form action="{{ route('admin.health_goals.destroy', $goal->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No Health Goals Found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Pagination Links -->
                            <div class="mt-3">
                                {{ $healthGoals->links('vendor.pagination.bootstrap-5') }}
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
