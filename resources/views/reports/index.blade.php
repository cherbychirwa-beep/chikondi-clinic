@extends('layouts.app')
@section('title', 'Reports')
@section('content')
    <h1 class="page-title mb-3">Reports</h1>
    <div class="row g-3">

        @can('reports.daily')
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-header">Daily appointment list</div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.daily') }}" class="row g-2 align-items-end">
                            @if($ownDoctor)
                                <div class="col-12 small text-muted">Doctor: <strong>{{ $ownDoctor->name }}</strong> (your own list)</div>
                            @else
                                <x-select name="doctor_id" label="Doctor" :required="true" col="col-md-6" placeholder="Select doctor…" :options="$doctors->mapWithKeys(fn ($d) => [$d->id => $d->name])->all()" />
                            @endif
                            <x-field name="date" label="Date" type="date" :value="today()->toDateString()" :required="true" col="col-md-4" />
                            <div class="col-12 d-flex gap-2 mt-2">
                                <button name="format" value="pdf" class="btn btn-primary" data-no-loading><i class="bi bi-file-earmark-pdf me-1"></i>PDF</button>
                                <button name="format" value="xlsx" class="btn btn-outline-success" data-no-loading><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
                            </div>
                        </form>
                    </div></div>
            </div>
        @endcan

        @can('reports.doctor_patients')
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-header">Patient list per doctor</div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.doctor-patients') }}" class="row g-2 align-items-end">
                            @if($ownDoctor)
                                <div class="col-12 small text-muted">Doctor: <strong>{{ $ownDoctor->name }}</strong> (your own patients)</div>
                            @else
                                <x-select name="doctor_id" label="Doctor" :required="true" col="col-md-8" placeholder="Select doctor…" :options="$doctors->mapWithKeys(fn ($d) => [$d->id => $d->name])->all()" />
                            @endif
                            <div class="col-12 mt-2"><button class="btn btn-outline-success" data-no-loading><i class="bi bi-file-earmark-excel me-1"></i>Download Excel</button></div>
                        </form>
                    </div></div>
            </div>
        @endcan

        @can('reports.visiting')
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-header">Visiting doctor schedule</div>
                    <div class="card-body">
                        <p class="small text-muted">Upcoming visit dates, durations and weekly hours for all active visiting doctors.</p>
                        <a href="{{ route('reports.visiting') }}" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
                    </div></div>
            </div>
        @endcan

        @can('reports.revenue')
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-header">Revenue &amp; attendance summary</div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.revenue') }}" class="row g-2 align-items-end">
                            <x-field name="from" label="From" type="date" :value="today()->startOfMonth()->toDateString()" :required="true" col="col-md-4" />
                            <x-field name="to" label="To" type="date" :value="today()->toDateString()" :required="true" col="col-md-4" />
                            <div class="col-md-4"><button class="btn btn-primary w-100">View report</button></div>
                        </form>
                    </div></div>
            </div>
        @endcan

        @can('payments.view')
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-header">Payment receipts</div>
                    <div class="card-body"><p class="small text-muted">Receipts are generated per payment as PDF from the payment records.</p>
                        <a href="{{ route('payments.index') }}" class="btn btn-outline-primary">Open payment records</a></div></div>
            </div>
        @endcan

        @can('prescriptions.view')
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-header">Patient prescription record</div>
                    <div class="card-body"><p class="small text-muted">Open a patient profile and use "Prescription record (PDF)".</p>
                        <a href="{{ route('patients.index') }}" class="btn btn-outline-primary">Find a patient</a></div></div>
            </div>
        @endcan
    </div>
@endsection
