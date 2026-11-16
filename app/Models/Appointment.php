<?php

namespace App\Models;

use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    public const SCHEDULED = 'scheduled';

    public const CHECKED_IN = 'checked_in';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    public const NO_SHOW = 'no_show';

    public const STATUSES = [
        self::SCHEDULED => 'Scheduled',
        self::CHECKED_IN => 'Checked-in',
        self::COMPLETED => 'Completed',
        self::CANCELLED => 'Cancelled',
        self::NO_SHOW => 'No-show',
    ];

    protected $fillable = [
        'appointment_number', 'patient_id', 'doctor_id', 'appointment_date', 'appointment_time',
        'status', 'slot_key', 'booked_by', 'checked_in_at', 'checked_in_by',
    ];

    protected function casts(): array
    {
        return ['appointment_date' => 'date', 'checked_in_at' => 'datetime'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function cancellation(): HasOne
    {
        return $this->hasOne(AppointmentCancellation::class);
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    public function getTimeShortAttribute(): string
    {
        return substr($this->appointment_time, 0, 5);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public static function makeSlotKey(int $doctorId, string $date, string $time): string
    {
        return $doctorId.'|'.$date.'|'.substr($time, 0, 5);
    }
}
