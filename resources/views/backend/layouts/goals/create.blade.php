@extends('backend.app', ['title' => 'Create Health Goal'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            {{-- Page Header --}}
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1 class="page-title mb-0">Create Health Goal</h1>
                <a href="{{ route('admin.health_goals.index') }}" class="btn btn-primary">Back to List</a>
            </div>

            {{-- Form Card --}}
            <div class="row mt-4">
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0">New Health Goal</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.health_goals.store') }}">
                                @csrf

                                {{-- User Dropdown --}}
                                <div class="mb-3">
                                    <label for="user_id" class="form-label fw-bold">Assign to User</label>
                                    <select name="user_id" id="user_id"
                                        class="form-control @error('user_id') is-invalid @enderror">
                                        <option value="">-- Select User --</option>
                                        @foreach (\App\Models\User::select('id', 'name', 'email')->withoutRole('admin')->get() as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Goal --}}
                                <div class="mb-3">
                                    <label for="goal" class="form-label fw-bold">Goal</label>
                                    <input type="text" name="goal" id="goal" placeholder="Enter goal"
                                        class="form-control @error('goal') is-invalid @enderror"
                                        value="{{ old('goal') }}" required>
                                    @error('goal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Methods --}}
                                <div class="mb-3">
                                    <label for="methods" class="form-label fw-bold">Methods</label>
                                    <textarea name="methods" id="methods" rows="5" placeholder="Describe methods"
                                        class="form-control summernote @error('methods') is-invalid @enderror">{{ old('methods') }}</textarea>
                                    @error('methods')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Timeline --}}
                                <div class="mb-3">
                                    <label for="timeline_years" class="form-label fw-bold">Timeline (Years)</label>
                                    <input type="number" step="0.1" name="timeline_years" id="timeline_years"
                                        placeholder="Enter timeline"
                                        class="form-control @error('timeline_years') is-invalid @enderror"
                                        value="{{ old('timeline_years') }}">
                                    @error('timeline_years')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Submit Button --}}
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="feather-plus-circle me-2"></i> Create Health Goal
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
{{-- Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#user_id').select2({
        width: '100%',
        placeholder: '-- Select User --',
        allowClear: true
    });

    // Initialize Summernote
    $('.summernote').summernote({
        height: 150,
        placeholder: 'Describe methods here...',
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview']]
        ]
    });
});
</script>
@endpush
