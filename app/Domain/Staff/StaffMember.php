<?php

namespace App\Domain\Staff;

use App\Exceptions\AuthorizationException;
use App\Models\User;

/**
 * Common domain abstraction for role-specific staff behaviour (navigation, dashboard, title).
 * Administrator, Receptionist, Attendant and Clinician (the doctor role) extend it.
 */
abstract class StaffMember
{
    public function __construct(protected User $user)
    {
    }

    abstract public function slug(): string;

    abstract public function title(): string;

    abstract public function dashboardView(): string;

    /** @return array<int,array{label:string,route:string,icon:string,can:string,active:string}> */
    abstract public function menu(): array;

    public function user(): User
    {
        return $this->user;
    }

    public function displayName(): string
    {
        return $this->user->name;
    }

    protected function item(string $label, string $route, string $icon, string $can, ?string $active = null): array
    {
        return ['label' => $label, 'route' => $route, 'icon' => $icon, 'can' => $can, 'active' => $active ?? $route];
    }

    public static function for(User $user): self
    {
        return match ($user->role?->slug) {
            'admin' => new Administrator($user),
            'receptionist' => new Receptionist($user),
            'attendant' => new Attendant($user),
            'doctor' => new Clinician($user),
            default => throw new AuthorizationException('Your account has no valid role assigned.'),
        };
    }
}
