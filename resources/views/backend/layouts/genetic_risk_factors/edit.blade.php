@extends('backend.app', ['title' => 'Edit Genetic Risk Factor'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                <div class="page-header">
                    <h1 class="page-title">Edit Genetic Risk Factor</h1>
                    <div class="ms-auto pageheader-btn">
                        <a href="{{ route('admin.genetic_risk_factors.index') }}" class="btn btn-primary">Back</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST"
                                    action="{{ route('admin.genetic_risk_factors.update', $geneticRiskFactor->id) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="title" class="form-control"
                                            value="{{ old('title', $geneticRiskFactor->title) }}" required>
                                        @error('title')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control summernote" rows="4">{{ old('description', $geneticRiskFactor->description) }}</textarea>
                                        @error('description')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary mt-2">Update</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
