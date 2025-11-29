@extends('backend.app', ['title' => 'Create Health Goal'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                <div class="page-header">
                    <h1 class="page-title">Create Health Goal</h1>
                    <div class="ms-auto pageheader-btn">
                        <a href="{{ route('admin.health_goals.index') }}" class="btn btn-primary">Back</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card post-sales-main">
                            <div class="card-header border-bottom">
                                <h3 class="card-title mb-0">New Health Goal</h3>
                            </div>
                            <div class="card-body border-0">
                                <form class="form form-horizontal" method="POST"
                                    action="{{ route('admin.health_goals.store') }}">
                                    @csrf
                                    <div class="row mb-4">

                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="goal" class="form-label">Goal:</label>
                                                <input type="text"
                                                    class="form-control @error('goal') is-invalid @enderror" name="goal"
                                                    id="goal" placeholder="Enter goal" value="{{ old('goal') }}">
                                                @error('goal')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="methods" class="form-label">Methods:</label>
                                                <textarea class="form-control summernote @error('methods') is-invalid @enderror" name="methods" id="methods"
                                                    placeholder="Enter methods" rows="4">{{ old('methods') }}</textarea>
                                                @error('methods')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>


                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="timeline_years" class="form-label">Timeline (Years):</label>
                                                <input type="number" step="0.1"
                                                    class="form-control @error('timeline_years') is-invalid @enderror"
                                                    name="timeline_years" id="timeline_years" placeholder="Enter timeline"
                                                    value="{{ old('timeline_years') }}">
                                                @error('timeline_years')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group mt-3">
                                            <button class="btn btn-primary" type="submit">Create Goal</button>
                                        </div>

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
