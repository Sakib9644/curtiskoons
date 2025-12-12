@extends('backend.app', ['title' => 'User Lab Reports'])

@section('content')

<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Lab Reports</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Reports</li>
                    </ol>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Test Dates</h3>
                </div>

                <div class="card-body">
                    @if($report->count() == 0)
                        <p class="text-center">No lab reports found.</p>
                    @else
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Test Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($report as $key => $item)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $item->test_date }}</td>
                                        <td>
                                            <a href="{{ route('admin.lab_reports.edit', $item->id) }}"
                                               class="btn btn-primary btn-sm">
                                                Edit Report
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
