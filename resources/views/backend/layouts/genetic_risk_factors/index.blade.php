@extends('backend.app', ['title' => 'Genetic Risk Factors'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                <div class="page-header">
                    <h1 class="page-title">Genetic Risk Factors</h1>
                    <div class="ms-auto pageheader-btn">
                        <a href="{{ route('admin.genetic_risk_factors.create') }}" class="btn btn-primary">Add New</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                     
                        <div class="card">
                            <div class="card-body">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Assgined User</th>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($factors as $factor)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $factor->user ? $factor->user->email . ' (' . $factor->user->name . ')' : 'N/A' }}
                                                </td>

                                                <td>{{ $factor->title }}</td>
                                                <td>{!! $factor->description !!}</td>
                                                <td>
                                                    <a href="{{ route('admin.genetic_risk_factors.edit', $factor->id) }}"
                                                        class="btn btn-sm btn-warning">Edit</a>
                                                    <form
                                                        action="{{ route('admin.genetic_risk_factors.destroy', $factor->id) }}"
                                                        method="POST" style="display:inline-block;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure?')">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                {{ $factors->links() }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
