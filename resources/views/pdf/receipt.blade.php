@extends('pdf.layout')
@section('body')
    <h2 style="text-align:center;text-transform:uppercase;letter-spacing:1px">Official payment receipt</h2>
    <table class="kv">
        <tr><td class="muted" width="38%">Receipt number</td><td><strong>{{ $payment->receipt_number }}</strong></td></tr>
        <tr><td class="muted">Date &amp; time</td><td>{{ $payment->paid_at->format('d M Y H:i') }}</td></tr>
        <tr><td class="muted">Patient</td><td>{{ $payment->patient->full_name }}</td></tr>
        <tr><td class="muted">Patient number</td><td>{{ $payment->patient->patient_number }}</td></tr>
        <tr><td class="muted">Doctor</td><td>{{ $payment->consultation->doctor->name }}</td></tr>
        <tr><td class="muted">Consultation date</td><td>{{ $payment->consultation->consultation_date->format('d M Y') }}</td></tr>
        <tr><td class="muted">Payment method</td><td>{{ $payment->method_label }}</td></tr>
        <tr><td class="muted">Received by</td><td>{{ $payment->recordedBy->name }}</td></tr>
    </table>
    <table style="margin-top:12px">
        <tr><td>Consultation fee</td><td class="right">{{ money($payment->consultation_fee) }}</td></tr>
        <tr><td><strong>Amount paid</strong></td><td class="right"><strong>{{ money($payment->amount_paid) }}</strong></td></tr>
        <tr><td>Balance remaining</td><td class="right">{{ money($payment->balance_after) }}</td></tr>
        <tr><td>Status</td><td class="right">{{ \App\Models\Payment::STATUSES[$payment->payment_status] ?? $payment->payment_status }}</td></tr>
    </table>
    <p class="muted" style="margin-top:18px">Thank you. Please keep this receipt for your records.</p>
@endsection
