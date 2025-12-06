@extends('backend.app', ['title' => 'User Management'])

@push('styles')
    <style>
        /* Avatar */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        /* Status Badge */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-active { background-color: #d1fae5; color: #065f46; }
        .status-inactive { background-color: #fee2e2; color: #991b1b; }

        /* Action buttons */
        .action-btn-group {
            display: flex;
            gap: 6px;
            justify-content: center;
        }

        /* Table hover */
        .table-hover tbody tr:hover { background-color: #f8fafc; }

        .page-title-area {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
    </style>
@endpush

@section('content')
<div class="app-content main-content">
    <div class="side-app">
        <div class="main-container">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-bottom bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title fw-semibold mb-0">Users List</h3>
                                <p class="text-muted mb-0">All users are loaded via server-side processing</p>
                            </div>
                            @can('insert')
                                <a href="{{ route('admin.users.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                                    <i class="mdi mdi-plus-circle-outline"></i> Add New User
                                </a>
                            @endcan
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-borderless" id="users-table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Group</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Group Modal -->
    <div class="modal fade" id="assignGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('admin.users.assignGroup') }}" method="POST" class="modal-content">
                @csrf
                <input type="hidden" name="user_id" id="assignUserId">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold">Assign Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="user-avatar d-inline-flex mb-2"><span id="userInitial">U</span></div>
                        <h6 id="userName" class="fw-semibold mb-1">User Name</h6>
                        <p class="text-muted mb-0">Select a group to assign</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Select Group</label>
                        <select name="group_id" class="form-select select2 group_id" required>
                            <option value="">Choose a group...</option>
                            @foreach (App\Models\Group::all() as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-info mb-0 d-flex align-items-center">
                        <i class="mdi mdi-information-outline me-2"></i>
                        <small>Assigning a new group will override existing group permissions.</small>
                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-check-circle-outline me-2"></i> Assign Group
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Lab Report Modal -->
    <div class="modal fade" id="uploadLabModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" enctype="multipart/form-data" class="modal-content" id="uploadLabForm">
                @csrf
                <input type="hidden" name="user_id" id="uploadUserId">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold">Upload Lab Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="user-avatar d-inline-flex mb-2"><span id="uploadUserInitial">U</span></div>
                        <h6 id="uploadUserName" class="fw-semibold mb-1">User Name</h6>
                        <p class="text-muted mb-0">Select a lab report file to upload</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Choose File</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                    <div class="alert alert-info mb-0">Allowed formats: PDF, JPG, PNG</div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Upload Lab Report</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.group_id').select2({
                width: '100%',
                placeholder: 'Select a group',
                dropdownParent: $('#assignGroupModal')
            });

            // Initialize DataTable
            $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.users.index') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'user', name: 'name' },
                    { data: 'email', name: 'email', visible: false, searchable: true },
                    { data: 'role', name: 'roles.name' },
                    { data: 'group', name: 'assignedgroup.name' },
                    { data: 'status', name: 'status' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[5, 'desc']],
                dom: 'lBfrtip',
            });
        });

        function assignGroup(userId, userName) {
            document.getElementById('assignUserId').value = userId;
            document.getElementById('userName').textContent = userName;
            document.getElementById('userInitial').textContent = userName.charAt(0).toUpperCase();
        }

        function uploadLab(userId, userName) {
            document.getElementById('uploadUserId').value = userId;
            document.getElementById('uploadUserName').textContent = userName;
            document.getElementById('uploadUserInitial').textContent = userName.charAt(0).toUpperCase();

            // Set the form action to the store route
            const form = document.getElementById('uploadLabForm');
            form.action = `/admin/users/${userId}/lab-reports`;
        }

        function confirmDelete(event) {
            event.preventDefault();
            if (confirm("Are you sure you want to delete this user?")) {
                event.target.submit();
            }
        }
    </script>
@endpush
