@extends('backend.app', ['title' => 'Supplements'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                {{-- Page Header --}}
                <div class="page-header d-flex justify-content-between align-items-center">
                    <h1 class="page-title mb-0">Supplements</h1>
                    @if (auth()->user()->can('insert'))
                        <a href="{{ route('admin.supplements.create') }}" class="btn btn-primary">Add New</a>
                    @endif
                </div>

                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card border">
                            <div class="card-body ">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="supplements-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Assigned User</th>
                                                <th>Name</th>
                                                <th>Dosage / Description</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {{-- Data will be loaded by DataTables --}}
                                        </tbody>
                                    </table>
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

@push('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $(function() {
            $('#supplements-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.supplements.index') }}',
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
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'dosage',
                        name: 'dosage'
                    }, // Summernote HTML content
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
