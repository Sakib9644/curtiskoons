@extends('backend.app', ['title' => 'Add 12-Week Plan'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            {{-- Page Header --}}
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1 class="page-title mb-0">Add 12-Week Plan</h1>
                <a href="{{ route('admin.twelve_week_plans.index') }}" class="btn btn-primary">Back</a>
            </div>

            {{-- Form Card --}}
            <div class="row mt-4">
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0">New Plan</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.twelve_week_plans.store') }}">
                                @csrf

                                {{-- User Dropdown --}}
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">Assign to User</label>
                                    <select name="user_id" id="user_id" class="form-control @error('user_id') is-invalid @enderror">
                                        <option value="">-- Select User --</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Title --}}
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                                           value="{{ old('title') }}" required>
                                    @error('title')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Description --}}
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" class="form-control summernote @error('description') is-invalid @enderror" rows="5">{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Submit Button --}}
                                <div class="d-grid mt-3">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="feather-plus-circle me-2"></i> Create Plan
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
            {{-- /Form Card --}}

        </div>
    </div>
</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 for user dropdown
    $('#user_id').select2({
        width: '100%',
        placeholder: '-- Select User --',
        allowClear: true
    });

    // Initialize Summernote
    $('.summernote').summernote({
        height: 200
    });
});
</script>
@endpush
