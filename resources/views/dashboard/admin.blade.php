@extends('layouts.app')
@section('title', 'Administrator Dashboard')
@section('content')
    <h1 class="page-title mb-3">Welcome, {{ auth()->user()->name }}</h1>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><x-stat-card label="Registered patients" :value="number_format($stats['patients'])" icon="bi-people" color="primary" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Active doctors" :value="$stats['doctors']" icon="bi-person-badge" color="info" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Active staff accounts" :value="$stats['staff']" icon="bi-person-gear" color="secondary" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Today's appointments" :value="$stats['appointments_today']" icon="bi-calendar-check" color="success" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Consultations completed today" :value="$stats['consultations_today']" icon="bi-clipboard2-pulse" color="success" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Today's revenue" :value="money($stats['revenue_today'])" icon="bi-cash-coin" color="success" /></div>
        <div class="col-12 col-xl-6"><x-stat-card label="Outstanding payments" :value="money($stats['outstanding'])" icon="bi-hourglass-split" color="danger" sub="Unpaid balance across all consultations" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header">Appointments – last 14 days</div>
                <div class="card-body"><canvas id="apptChart" height="140"></canvas></div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header">Revenue (MWK) – last 14 days</div>
                <div class="card-body"><canvas id="revChart" height="140"></canvas></div></div>
        </div>
    </div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const appt = @json($stats['appointment_trend']);
    const rev = @json($stats['revenue_trend']);
    new Chart(document.getElementById('apptChart'), {
        type: 'bar',
        data: { labels: appt.labels, datasets: [{ label: 'Appointments', data: appt.values, backgroundColor: '#13315c', borderRadius: 4 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
    new Chart(document.getElementById('revChart'), {
        type: 'line',
        data: { labels: rev.labels, datasets: [{ label: 'Revenue (MWK)', data: rev.values, borderColor: '#12a594', backgroundColor: 'rgba(18,165,148,.15)', fill: true, tension: .3 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
</script>
@endpush
