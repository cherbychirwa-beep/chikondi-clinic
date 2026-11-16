@extends('layouts.app')
@section('title', 'Revenue & attendance')
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>@endsection
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="page-title mb-0">Revenue &amp; attendance summary</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="{{ route('reports.revenue.download', ['from' => $summary['from'], 'to' => $summary['to'], 'format' => 'pdf']) }}"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
            <a class="btn btn-outline-success" href="{{ route('reports.revenue.download', ['from' => $summary['from'], 'to' => $summary['to'], 'format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
        </div>
    </div>

    <form method="GET" class="card card-body mb-3 row g-2 align-items-end flex-row">
        <x-field name="from" label="From" type="date" :value="$summary['from']" :required="true" col="col-md-3" />
        <x-field name="to" label="To" type="date" :value="$summary['to']" :required="true" col="col-md-3" />
        <div class="col-auto"><button class="btn btn-primary">Update</button></div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><x-stat-card label="Total appointments" :value="$summary['total_appointments']" icon="bi-calendar-check" color="primary" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Completed" :value="$summary['completed']" icon="bi-check2-circle" color="success" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Cancelled" :value="$summary['cancelled']" icon="bi-calendar-x" color="danger" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="No-shows" :value="$summary['no_show']" icon="bi-person-x" color="warning" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Total consultations" :value="$summary['total_consultations']" icon="bi-clipboard2-pulse" color="info" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Total revenue" :value="money($summary['total_revenue'])" icon="bi-cash-coin" color="success" /></div>
        <div class="col-12 col-xl-6"><x-stat-card label="Outstanding balances (consultations in range)" :value="money($summary['outstanding'])" icon="bi-hourglass-split" color="danger" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8"><div class="card h-100"><div class="card-header">Daily revenue (MWK)</div><div class="card-body"><canvas id="dailyChart" height="120"></canvas></div></div></div>
        <div class="col-lg-4"><div class="card h-100"><div class="card-header">Payment methods</div><div class="card-body">
                    @if(count($summary['methods']))<canvas id="methodChart" height="200"></canvas>
                    @else<div class="empty-state">No payments in this period.</div>@endif
                </div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card"><div class="card-header">Attendance statistics</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span>Completion rate</span><strong>{{ $summary['attendance']['completion_rate'] }}%</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Cancellation rate</span><strong>{{ $summary['attendance']['cancellation_rate'] }}%</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>No-show rate</span><strong>{{ $summary['attendance']['no_show_rate'] }}%</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Still scheduled / checked-in</span><strong>{{ $summary['scheduled'] }} / {{ $summary['checked_in'] }}</strong></li>
                </ul></div>
        </div>
        <div class="col-lg-7">
            <div class="card"><div class="card-header">Attendance per doctor</div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>Doctor</th><th class="text-end">Total</th><th class="text-end">Completed</th><th class="text-end">Cancelled</th><th class="text-end">No-show</th></tr></thead>
                    <tbody>
                    @forelse($summary['attendance']['per_doctor'] as $d)
                        <tr><td>{{ $d['doctor'] }}</td><td class="text-end">{{ $d['total'] }}</td><td class="text-end">{{ $d['completed'] }}</td><td class="text-end">{{ $d['cancelled'] }}</td><td class="text-end">{{ $d['no_show'] }}</td></tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state py-3">No appointments in this period.</div></td></tr>
                    @endforelse
                    </tbody></table></div></div>
        </div>
    </div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const daily = @json($summary['daily_revenue']);
    new Chart(document.getElementById('dailyChart'), {
        type: 'bar',
        data: { labels: daily.map(d => d.date), datasets: [{ label: 'Revenue (MWK)', data: daily.map(d => d.total), backgroundColor: '#12a594', borderRadius: 4 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
    const methods = @json($summary['methods']);
    if (methods.length) {
        new Chart(document.getElementById('methodChart'), {
            type: 'doughnut',
            data: { labels: methods.map(m => m.method), datasets: [{ data: methods.map(m => m.total), backgroundColor: ['#13315c', '#12a594', '#f4a261', '#8d99ae'] }] },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    }
</script>
@endpush
