@extends('backend.app', ['title' => 'Health Goals'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                {{-- Page Header --}}
                <div class="page-header d-flex justify-content-between align-items-center">
                    <h1 class="page-title">Health Goals</h1>
                    @if (auth()->user()->can('insert'))
                        <a href="{{ route('admin.health_goals.create') }}" class="btn btn-primary">Create New Goal</a>
                    @endif
                </div>

                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Health Goals List</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="health-goals-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Assigned User</th>
                                                <th>Goal</th>
                                                <th>Methods</th>
                                                <th>Timeline (years)</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                    </table>
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $(function() {
            $('#health-goals-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.health_goals.index') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'user',
                        name: 'user.email'
                    },
                    {
                        data: 'goal',
                        name: 'goal'
                    },
                    {
                        data: 'methods',
                        name: 'methods'
                    },
                    {
                        data: 'timeline_years',
                        name: 'timeline_years'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [1, 'desc']
                ]
            });
        });
    </script>
@endpush
