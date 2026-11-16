<?php

namespace App\Domain\Doctors;

use App\Contracts\Bookable;
use App\Models\Doctor as DoctorRecord;
use Carbon\CarbonInterface;

/**
 * Abstract domain-level Doctor.
 *
 * Eloquent models must extend Illuminate\Database\Eloquent\Model, so the employment-type
 * hierarchy lives here as a domain layer that wraps the persisted App\Models\Doctor record
 * (composition + inheritance + polymorphism, no conflicting multiple inheritance).
 */
abstract class Doctor implements Bookable
{
    public function __construct(protected DoctorRecord $record)
    {
    }

    abstract public function employmentType(): string;

    abstract public function employmentLabel(): string;

    abstract public function isAvailableOn(CarbonInterface $date): bool;

    abstract public function availabilitySummary(): string;

    public function record(): DoctorRecord
    {
        return $this->record;
    }

    public function displayName(): string
    {
        return 'Dr. '.preg_replace('/^dr\.?\s+/i', '', $this->record->user->name ?? 'Unknown');
    }

    /** Factory returning the correct subclass for the stored employment type. */
    public static function from(DoctorRecord $record): self
    {
        return match ($record->employment_type) {
            'visiting' => new VisitingDoctor($record),
            default => new FullTimeDoctor($record),
        };
    }
}
