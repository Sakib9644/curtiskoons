@extends('backend.app', ['title' => 'Supplements'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                <div class="page-header">
                    <h1 class="page-title">Supplements</h1>
                    <div class="ms-auto pageheader-btn">
                        <a href="{{ route('admin.supplements.create') }}" class="btn btn-primary">Add New</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <div class="card">
                            <div class="card-body">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Dosage</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($supplements as $supplement)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $supplement->name }}</td>
                                                <td>{!! $supplement->dosage !!}</td>
                                                <td>
                                                    <a href="{{ route('admin.supplements.edit', $supplement->id) }}"
                                                        class="btn btn-sm btn-warning">Edit</a>
                                                    <form action="{{ route('admin.supplements.destroy', $supplement->id) }}"
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
                                {{ $supplements->links() }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
