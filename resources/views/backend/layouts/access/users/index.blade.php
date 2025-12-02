@extends('backend.app', ['title' => 'Users'])

@push('styles')
    <link href="{{ asset('default/datatable.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">

            <div class="main-container container-fluid">

                <!-- PAGE HEADER -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Users</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">Access</li>
                            <li class="breadcrumb-item">Users</li>
                        </ol>
                    </div>
                </div>

                <!-- USERS TABLE -->
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="card product-sales-main">
                            <div class="card-header border-bottom">
                                <h3 class="card-title mb-0">List</h3>
                                @can('insert')
                                    <div class="card-options ms-auto">
                                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">Add</a>
                                    </div>
                                @endcan
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered text-nowrap border-bottom" id="datatable">
                                    <thead>
                                        <tr>
                                            <th>SN</th>
                                            <th>Name</th>
                                            <th>Slug</th>
                                            <th>Created</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $sn = 1; @endphp
                                        @forelse ($users as $user)
                                            <tr>
                                                <td>{{ $sn++ }}</td>
                                                <td>{{ $user->name }}</td>
                                                <td>{{ $user->slug }}</td>
                                                <td>{{ \Carbon\Carbon::parse($user->created_at)->format('d-m-Y') }}</td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group" aria-label="Actions">

                                                        <!-- View button (everyone can see) -->


                                                        <!-- Update buttons -->
                                                        @can('view')
                                                            <a href="{{ route('admin.users.show', $user->id) }}"
                                                                class="btn btn-info">
                                                                <i class="mdi mdi-eye"></i>
                                                            </a>
                                                        @endcan
                                                        @can('update')
                                                            <a href="{{ route('admin.users.status', $user->id) }}"
                                                                class="btn btn-warning">
                                                                @if ($user->status == 'active')
                                                                    <i class="fa-solid fa-lock-open"></i>
                                                                @else
                                                                    <i class="fa-solid fa-lock"></i>
                                                                @endif
                                                            </a>

                                                            <a href="{{ route('admin.users.edit', $user->id) }}"
                                                                class="btn btn-primary">
                                                                <i class="mdi mdi-pencil"></i>
                                                            </a>
                                                        @endcan

                                                        <!-- Delete button -->
                                                        @can('delete')
                                                            <form action="{{ route('admin.users.destroy', $user->id) }}"
                                                                method="POST" onsubmit="return confirm('Are you sure?')"
                                                                style="display:inline;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">
                                                                    <i class="mdi mdi-delete"></i>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="100" class="text-center">No Data</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <!-- Pagination -->
                                <div class="d-flex justify-content-center mt-4">
                                    {{ $users->links('vendor.pagination.bootstrap-5') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Add any custom JS here -->
@endpush
