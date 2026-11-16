@extends('pdf.layout')
@section('body')
    <h2>Revenue &amp; attendance summary</h2>
    <p class="muted">Period: {{ \Carbon\Carbon::parse($summary['from'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($summary['to'])->format('d M Y') }}</p>

    <table>
        <thead><tr><th colspan="2">Key figures</th></tr></thead>
        <tbody>
        <tr><td>Total appointments</td><td class="right">{{ $summary['total_appointments'] }}</td></tr>
        <tr><td>Completed appointments</td><td class="right">{{ $summary['completed'] }}</td></tr>
        <tr><td>Cancelled appointments</td><td class="right">{{ $summary['cancelled'] }}</td></tr>
        <tr><td>No-show appointments</td><td class="right">{{ $summary['no_show'] }}</td></tr>
        <tr><td>Total consultations</td><td class="right">{{ $summary['total_consultations'] }}</td></tr>
        <tr><td><strong>Total revenue</strong></td><td class="right"><strong>{{ money($summary['total_revenue']) }}</strong></td></tr>
        <tr><td>Outstanding balances</td><td class="right">{{ money($summary['outstanding']) }}</td></tr>
        </tbody>
    </table>

    <h2>Attendance statistics</h2>
    <table>
        <tbody>
        <tr><td>Completion rate</td><td class="right">{{ $summary['attendance']['completion_rate'] }}%</td></tr>
        <tr><td>Cancellation rate</td><td class="right">{{ $summary['attendance']['cancellation_rate'] }}%</td></tr>
        <tr><td>No-show rate</td><td class="right">{{ $summary['attendance']['no_show_rate'] }}%</td></tr>
        </tbody>
    </table>

    <h2>Attendance per doctor</h2>
    <table>
        <thead><tr><th>Doctor</th><th class="right">Total</th><th class="right">Completed</th><th class="right">Cancelled</th><th class="right">No-show</th></tr></thead>
        <tbody>
        @forelse($summary['attendance']['per_doctor'] as $d)
            <tr><td>{{ $d['doctor'] }}</td><td class="right">{{ $d['total'] }}</td><td class="right">{{ $d['completed'] }}</td><td class="right">{{ $d['cancelled'] }}</td><td class="right">{{ $d['no_show'] }}</td></tr>
        @empty
            <tr><td colspan="5" class="muted">No appointments in this period.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Payment method breakdown</h2>
    <table>
        <thead><tr><th>Method</th><th class="right">Payments</th><th class="right">Total</th></tr></thead>
        <tbody>
        @forelse($summary['methods'] as $m)
            <tr><td>{{ $m['method'] }}</td><td class="right">{{ $m['count'] }}</td><td class="right">{{ money($m['total']) }}</td></tr>
        @empty
            <tr><td colspan="3" class="muted">No payments in this period.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Daily revenue trend</h2>
    @php($max = max(1, collect($summary['daily_revenue'])->max('total')))
    <table>
        <thead><tr><th width="18%">Date</th><th>Revenue</th><th class="right" width="22%">Amount</th></tr></thead>
        <tbody>
        @foreach($summary['daily_revenue'] as $d)
            <tr><td>{{ \Carbon\Carbon::parse($d['date'])->format('d M Y') }}</td>
                <td><div style="background:#12a594;height:8px;width:{{ round($d['total'] / $max * 100) }}%"></div></td>
                <td class="right">{{ money($d['total']) }}</td></tr>
        @endforeach
        </tbody>
    </table>
@endsection
