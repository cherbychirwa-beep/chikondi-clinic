<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends Model
{
    public const DAYS = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

    protected $fillable = ['doctor_id', 'day_of_week', 'start_time', 'end_time', 'slot_minutes', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'day_of_week' => 'integer', 'slot_minutes' => 'integer'];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '';
    }

    public function getStartShortAttribute(): string
    {
        return substr($this->start_time, 0, 5);
    }

    public function getEndShortAttribute(): string
    {
        return substr($this->end_time, 0, 5);
    }
}
