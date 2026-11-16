<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_number', 'full_name', 'date_of_birth', 'gender', 'address', 'phone',
        'next_of_kin_name', 'next_of_kin_phone', 'primary_doctor_id', 'registered_on', 'registered_by',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'registered_on' => 'date'];
    }

    public function primaryDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'primary_doctor_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }

    /** Search by patient number, name, phone, or an exact date of birth (YYYY-MM-DD). */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $w) use ($term) {
            $like = '%'.$term.'%';
            $w->where('patient_number', 'like', $like)
                ->orWhere('full_name', 'like', $like)
                ->orWhere('phone', 'like', $like);

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $term)) {
                $w->orWhereDate('date_of_birth', $term);
            }
        });
    }
}
