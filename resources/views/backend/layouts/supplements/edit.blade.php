@extends('backend.app', ['title' => 'Edit Supplement'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                <div class="page-header">
                    <h1 class="page-title">Edit Supplement</h1>
                    <div class="ms-auto pageheader-btn">
                        <a href="{{ route('admin.supplements.index') }}" class="btn btn-primary">Back</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.supplements.update', $supplement->id) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="form-group">
                                        <label>Name</label>
                                        <input type="text" name="name" class="form-control"
                                            value="{{ old('name', $supplement->name) }}" required>
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label>Dosage</label>
                                        <textarea name="dosage" class="form-control summernote" rows="4">{{ old('dosage', $supplement->dosage) }}</textarea>
                                        @error('dosage')
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
