<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS = [
        'cash' => 'Cash',
        'mobile_money' => 'Mobile Money',
        'bank_transfer' => 'Bank Transfer',
        'other' => 'Other',
    ];

    public const STATUSES = ['partial' => 'Partial', 'paid' => 'Paid in full'];

    protected $fillable = [
        'receipt_number', 'patient_id', 'consultation_id', 'consultation_fee', 'amount_paid',
        'balance_after', 'payment_method', 'payment_status', 'paid_at', 'recorded_by', 'submission_token',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'consultation_fee' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->payment_method] ?? $this->payment_method;
    }
}
