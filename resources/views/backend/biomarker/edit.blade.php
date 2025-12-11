@extends('backend.app', ['title' => 'Edit Biomarker'])

@section('content')

<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Biomarker</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.biomarker.index') }}">Biomarkers</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h3 class="card-title mb-0">Edit Biomarker: {{ $biomarker->label }}</h3>
                            <div class="card-options">
                                <a href="{{ route('admin.biomarker.index') }}" class="btn btn-sm btn-primary">Back</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.biomarker.update', $biomarker->id) }}">
                                @csrf
                                @method('PUT')

                                <!-- Basic Info -->
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="label" class="form-label">Label:</label>
                                            <input type="text" class="form-control @error('label') is-invalid @enderror"
                                                name="label" id="label" value="{{ old('label', $biomarker->label) }}" required>
                                            @error('label')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="unit" class="form-label">Unit:</label>
                                            <input type="text" class="form-control @error('unit') is-invalid @enderror"
                                                name="unit" id="unit" value="{{ old('unit', $biomarker->unit) }}">
                                            @error('unit')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="category" class="form-label">Category:</label>
                                            <select class="form-control @error('category') is-invalid @enderror"
                                                name="category" id="category" required>
                                                <option value="metabolic" {{ $biomarker->category == 'metabolic' ? 'selected' : '' }}>Metabolic</option>
                                                <option value="liver" {{ $biomarker->category == 'liver' ? 'selected' : '' }}>Liver</option>
                                                <option value="kidney" {{ $biomarker->category == 'kidney' ? 'selected' : '' }}>Kidney</option>
                                                <option value="inflammation" {{ $biomarker->category == 'inflammation' ? 'selected' : '' }}>Inflammation</option>
                                                <option value="lipids" {{ $biomarker->category == 'lipids' ? 'selected' : '' }}>Lipids</option>
                                                <option value="blood" {{ $biomarker->category == 'blood' ? 'selected' : '' }}>Blood</option>
                                                <option value="genetic" {{ $biomarker->category == 'genetic' ? 'selected' : '' }}>Genetic</option>
                                            </select>
                                            @error('category')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                @if($biomarker->is_numeric)
                                <!-- Numeric Ranges -->
                                <h4 class="mb-3">Ranges</h4>
                                <div id="ranges-container">
                                    @foreach($biomarker->ranges as $index => $range)
                                    <div class="row mb-3 range-row">
                                        <div class="col-md-3">
                                            <label class="form-label">Range Start:</label>
                                            <input type="number" step="0.01" class="form-control"
                                                name="ranges[{{ $index }}][range_start]"
                                                value="{{ $range->range_start }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Range End:</label>
                                            <input type="number" step="0.01" class="form-control"
                                                name="ranges[{{ $index }}][range_end]"
                                                value="{{ $range->range_end }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Delta (years):</label>
                                            <input type="number" step="0.1" class="form-control"
                                                name="ranges[{{ $index }}][delta]"
                                                value="{{ $range->delta }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-danger btn-block remove-range">Remove</button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-success mb-4" id="add-range">Add Range</button>

                                @else
                                <!-- Genetic Variants -->
                                <h4 class="mb-3">Genetic Variants</h4>
                                <div id="genetics-container">
                                    @foreach($biomarker->genetics as $index => $genetic)
                                    <div class="row mb-3 genetic-row">
                                        <div class="col-md-4">
                                            <label class="form-label">Genotype:</label>
                                            <input type="text" class="form-control"
                                                name="genetics[{{ $index }}][genotype]"
                                                value="{{ $genetic->genotype }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Delta (years):</label>
                                            <input type="number" step="0.1" class="form-control"
                                                name="genetics[{{ $index }}][delta]"
                                                value="{{ $genetic->delta }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-danger btn-block remove-genetic">Remove</button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-success mb-4" id="add-genetic">Add Variant</button>
                                @endif

                                <div class="form-group mt-4">
                                    <button class="btn btn-primary" type="submit">Update Biomarker</button>
                                    <a href="{{ route('admin.biomarker.index') }}" class="btn btn-secondary">Cancel</a>
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
<script>
$(document).ready(function() {
    let rangeIndex = {{ $biomarker->ranges->count() }};
    let geneticIndex = {{ $biomarker->genetics->count() }};

    // Add new range
    $('#add-range').click(function() {
        let html = `
            <div class="row mb-3 range-row">
                <div class="col-md-3">
                    <label class="form-label">Range Start:</label>
                    <input type="number" step="0.01" class="form-control"
                        name="ranges[${rangeIndex}][range_start]" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Range End:</label>
                    <input type="number" step="0.01" class="form-control"
                        name="ranges[${rangeIndex}][range_end]" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Delta (years):</label>
                    <input type="number" step="0.1" class="form-control"
                        name="ranges[${rangeIndex}][delta]" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-block remove-range">Remove</button>
                </div>
            </div>
        `;
        $('#ranges-container').append(html);
        rangeIndex++;
    });

    // Remove range
    $(document).on('click', '.remove-range', function() {
        $(this).closest('.range-row').remove();
    });

    // Add new genetic variant
    $('#add-genetic').click(function() {
        let html = `
            <div class="row mb-3 genetic-row">
                <div class="col-md-4">
                    <label class="form-label">Genotype:</label>
                    <input type="text" class="form-control"
                        name="genetics[${geneticIndex}][genotype]" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Delta (years):</label>
                    <input type="number" step="0.1" class="form-control"
                        name="genetics[${geneticIndex}][delta]" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-block remove-genetic">Remove</button>
                </div>
            </div>
        `;
        $('#genetics-container').append(html);
        geneticIndex++;
    });

    // Remove genetic variant
    $(document).on('click', '.remove-genetic', function() {
        $(this).closest('.genetic-row').remove();
    });
});
</script>
@endpush
