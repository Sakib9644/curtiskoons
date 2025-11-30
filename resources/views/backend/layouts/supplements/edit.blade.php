@extends('backend.app', ['title' => 'Edit Supplement'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            {{-- Page Header --}}
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1 class="page-title mb-0">Edit Supplement</h1>
                <a href="{{ route('admin.supplements.index') }}" class="btn btn-primary">Back to List</a>
            </div>

            {{-- Form Card --}}
            <div class="row mt-4">
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0">Update Supplement</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.supplements.update', $supplement->id) }}">
                                @csrf
                                @method('PUT')

                                {{-- User Dropdown --}}
                                <div class="mb-3">
                                    <label for="user_id" class="form-label fw-bold">Assign to User</label>
                                    <select name="user_id" id="user_id"
                                        class="form-control @error('user_id') is-invalid @enderror">
                                        <option value="">-- Select User --</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}"
                                                {{ $user->id == old('user_id', $supplement->user_id) ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Name --}}
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-bold">Supplement Name</label>
                                    <input type="text" name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $supplement->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Dosage / Description --}}
                                <div class="mb-3">
                                    <label for="dosage" class="form-label fw-bold">Dosage / Description</label>
                                    <textarea name="dosage" id="dosage" rows="5"
                                        class="form-control summernote @error('dosage') is-invalid @enderror"
                                        placeholder="Enter dosage or description">{{ old('dosage', $supplement->dosage) }}</textarea>
                                    @error('dosage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Submit Button --}}
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="feather-save me-2"></i> Update Supplement
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

{{-- Summernote --}}
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('#user_id').select2({
        width: '100%',
        placeholder: '-- Select User --',
        allowClear: true
    });

    // Initialize Summernote
    $('.summernote').summernote({
        height: 150,
        placeholder: 'Enter dosage or description...',
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
