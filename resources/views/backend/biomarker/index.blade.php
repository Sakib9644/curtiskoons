@extends('backend.app', ['title' => 'Biomarkers'])

@section('content')

<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Biomarkers</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Biomarkers</a></li>
                        <li class="breadcrumb-item active" aria-current="page">List</li>
                    </ol>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">All Biomarkers</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Label</th>
                                            <th>Unit</th>
                                            <th>Category</th>
                                            <th>Type</th>
                                            <th>Ranges/Genetics</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($biomarkers as $biomarker)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $biomarker->name }}</td>
                                            <td>{{ $biomarker->label }}</td>
                                            <td>{{ $biomarker->unit ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ ucfirst($biomarker->category) }}</span>
                                            </td>
                                            <td>
                                                @if($biomarker->is_numeric)
                                                <span class="badge bg-primary">Numeric</span>
                                                @else
                                                <span class="badge bg-warning">Genetic</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($biomarker->is_numeric)
                                                {{ $biomarker->ranges->count() }} ranges
                                                @else
                                                {{ $biomarker->genetics->count() }} variants
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.biomarker.edit', $biomarker->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i> Edit
                                                </a>
                                                <form action="{{ route('admin.biomarker.destroy', $biomarker->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No biomarkers found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{ $biomarkers->links() }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
