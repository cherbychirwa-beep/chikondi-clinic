<?php

namespace App\Models;

use App\Domain\Doctors\Doctor as DoctorDomain;
use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    public const FULL_TIME = 'full_time';

    public const VISITING = 'visiting';

    protected $fillable = [
        'user_id', 'doctor_number', 'phone', 'consulting_room', 'employment_type',
        'next_visit_date', 'next_visit_duration_days', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'next_visit_date' => 'date',
            'next_visit_duration_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'primary_doctor_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getNameAttribute(): string
    {
        return $this->domain()->displayName();
    }

    /** Polymorphic domain object (FullTimeDoctor / VisitingDoctor). */
    public function domain(): DoctorDomain
    {
        return DoctorDomain::from($this);
    }

    public function isVisiting(): bool
    {
        return $this->employment_type === self::VISITING;
    }
}
