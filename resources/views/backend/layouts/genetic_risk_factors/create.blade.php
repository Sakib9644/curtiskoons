@extends('backend.app', ['title' => 'Add Genetic Risk Factor'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            {{-- Page Header --}}
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1 class="page-title">Add Genetic Risk Factor</h1>
                <a href="{{ route('admin.genetic_risk_factors.index') }}" class="btn btn-primary">Back</a>
            </div>

            {{-- Form Card --}}
            <div class="row justify-content-center mt-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">New Genetic Risk Factor</h4>
                        </div>
                        <div class="card-body">

                            <form method="POST" action="{{ route('admin.genetic_risk_factors.store') }}">
                                @csrf

                                {{-- User Assignment --}}
                                <div class="mb-3">
                                    <label for="user_id" class="form-label fw-bold">Assign to User</label>
                                    <select name="user_id" id="user_id"
                                        class="form-control @error('user_id') is-invalid @enderror">
                                        <option value="">-- Select User --</option>
                                        @foreach (\App\Models\User::select('id', 'name', 'email')->get() as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Title --}}
                                <div class="mb-3">
                                    <label for="title" class="form-label fw-bold">Title</label>
                                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                                        value="{{ old('title') }}" placeholder="Enter title" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Description --}}
                                <div class="mb-3">
                                    <label for="description" class="form-label fw-bold">Description</label>
                                    <textarea name="description" id="description" class="form-control summernote @error('description') is-invalid @enderror"
                                        rows="5" placeholder="Enter description">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Submit Button --}}
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fa fa-plus-circle me-1"></i> Add Factor
                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>

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
});
</script>

{{-- Summernote --}}
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<script>
$(document).ready(function() {
    $('.summernote').summernote({
        height: 150,
        placeholder: 'Enter description here...',
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
