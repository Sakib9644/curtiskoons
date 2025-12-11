@extends('backend.app', ['title' => 'Edit Lab Report'])

@section('content')

<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Edit Lab Report</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.lab_reports.update', $report->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">

                    <!-- Patient Info -->
                    <div class="col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">Patient Information</h3></div>
                            <div class="card-body row">
                                <div class="col-md-4 mb-3">
                                    <label>Patient Name</label>
                                    <input type="text" name="patient_name" class="form-control" value="{{ old('patient_name', $report->patient_name) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $report->date_of_birth) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Test Date</label>
                                    <input type="date" name="test_date" class="form-control" value="{{ old('test_date', $report->test_date) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Chronological Age</label>
                                    <input type="number" step="0.1" name="chronological_age" class="form-control" value="{{ old('chronological_age', $report->chronological_age) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metabolic Panel -->
                    <div class="col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">Metabolic Panel</h3></div>
                            <div class="card-body row">
                                <div class="col-md-3 mb-3">
                                    <label>Fasting Glucose (mg/dL)</label>
                                    <input type="number" step="0.1" name="fasting_glucose" class="form-control" value="{{ old('fasting_glucose', $report->fasting_glucose) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>HbA1c (%)</label>
                                    <input type="number" step="0.01" name="hba1c" class="form-control" value="{{ old('hba1c', $report->hba1c) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>Fasting Insulin (µU/mL)</label>
                                    <input type="number" step="0.1" name="fasting_insulin" class="form-control" value="{{ old('fasting_insulin', $report->fasting_insulin) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>HOMA-IR (calculated)</label>
                                    <input type="number" step="0.01" name="homa_ir" class="form-control" value="{{ old('homa_ir', $report->homa_ir) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Liver Function Panel -->
                    <div class="col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">Liver Function Panel</h3></div>
                            <div class="card-body row">
                                <div class="col-md-3 mb-3">
                                    <label>ALT (U/L)</label>
                                    <input type="number" step="0.1" name="alt" class="form-control" value="{{ old('alt', $report->alt) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>AST (U/L)</label>
                                    <input type="number" step="0.1" name="ast" class="form-control" value="{{ old('ast', $report->ast) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>GGT (U/L)</label>
                                    <input type="number" step="0.1" name="ggt" class="form-control" value="{{ old('ggt', $report->ggt) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kidney Function Panel -->
                    <div class="col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">Kidney Function Panel</h3></div>
                            <div class="card-body row">
                                <div class="col-md-3 mb-3">
                                    <label>Serum Creatinine (mg/dL)</label>
                                    <input type="number" step="0.01" name="serum_creatinine" class="form-control" value="{{ old('serum_creatinine', $report->serum_creatinine) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>eGFR (mL/min/1.73m²)</label>
                                    <input type="number" step="0.1" name="egfr" class="form-control" value="{{ old('egfr', $report->egfr) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inflammation Panel -->
                    <div class="col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">Inflammation Panel</h3></div>
                            <div class="card-body row">
                                <div class="col-md-3 mb-3">
                                    <label>hs-CRP (mg/L)</label>
                                    <input type="number" step="0.01" name="hs_crp" class="form-control" value="{{ old('hs_crp', $report->hs_crp) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>Homocysteine (µmol/L)</label>
                                    <input type="number" step="0.1" name="homocysteine" class="form-control" value="{{ old('homocysteine', $report->homocysteine) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lipid Panel -->
                    <div class="col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">Lipid Panel</h3></div>
                            <div class="card-body row">
                                <div class="col-md-3 mb-3">
                                    <label>Triglycerides (mg/dL)</label>
                                    <input type="number" step="0.1" name="triglycerides" class="form-control" value="{{ old('triglycerides', $report->triglycerides) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>HDL Cholesterol (mg/dL)</label>
                                    <input type="number" step="0.1" name="hdl_cholesterol" class="form-control" value="{{ old('hdl_cholesterol', $report->hdl_cholesterol) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>Lp(a) (mg/dL)</label>
                                    <input type="number" step="0.1" name="lp_a" class="form-control" value="{{ old('lp_a', $report->lp_a) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hematologic Panel -->
                    <div class="col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">Hematologic Panel</h3></div>
                            <div class="card-body row">
                                <div class="col-md-3 mb-3">
                                    <label>WBC Count (×10⁹/L)</label>
                                    <input type="number" step="0.1" name="wbc_count" class="form-control" value="{{ old('wbc_count', $report->wbc_count) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>Lymphocyte %</label>
                                    <input type="number" step="0.1" name="lymphocyte_percentage" class="form-control" value="{{ old('lymphocyte_percentage', $report->lymphocyte_percentage) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>RDW (%)</label>
                                    <input type="number" step="0.1" name="rdw" class="form-control" value="{{ old('rdw', $report->rdw) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>Albumin (g/dL)</label>
                                    <input type="number" step="0.1" name="albumin" class="form-control" value="{{ old('albumin', $report->albumin) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Genetic Panel -->
                    <div class="col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">Genetic Markers</h3></div>
                            <div class="card-body row">
                                <div class="col-md-3 mb-3">
                                    <label>APOE Genotype</label>
                                    <input type="text" name="apoe_genotype" class="form-control" value="{{ old('apoe_genotype', $report->apoe_genotype) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>MTHFR C677T</label>
                                    <input type="text" name="mthfr_c677t" class="form-control" value="{{ old('mthfr_c677t', $report->mthfr_c677t) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-lg-12 text-end">
                        <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Update Lab Report</button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

@endsection
