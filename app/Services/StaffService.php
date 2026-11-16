<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

class StaffService
{
    public function __construct(
        protected DatabaseConnection $db,
        protected AuditService $audit,
    ) {
    }

    public function create(array $data, User $by): User
    {
        return $this->db->transaction(function () use ($data, $by) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role_id' => $data['role_id'],
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $this->audit->log('staff.created', "Created staff account {$user->username} with role {$user->role->name}", $user, $by);

            return $user;
        });
    }

    /** @throws ValidationException */
    public function update(User $user, array $data, User $by): User
    {
        $roleChanged = (int) $data['role_id'] !== (int) $user->role_id;

        if ($roleChanged && $user->id === $by->id) {
            throw new ValidationException('You cannot change your own role.', ['role_id' => 'You cannot change your own role.']);
        }
        if ($roleChanged && $user->hasRole('admin') && $this->isLastActiveAdmin($user)) {
            throw new ValidationException('The last active administrator cannot be demoted.', ['role_id' => 'The last active administrator cannot be demoted.']);
        }

        return $this->db->transaction(function () use ($user, $data, $by, $roleChanged) {
            $oldRole = $user->role->name;
            $user->update(['name' => $data['name'], 'email' => $data['email'] ?? null, 'role_id' => $data['role_id']]);

            $this->audit->log('staff.updated', "Updated staff account {$user->username}", $user, $by);
            if ($roleChanged) {
                $user->load('role');
                $this->audit->log('role.changed', "Role of {$user->username} changed from {$oldRole} to {$user->role->name}", $user, $by);
            }

            return $user;
        });
    }

    /** @throws ValidationException */
    public function setActive(User $user, bool $active, User $by): User
    {
        if (! $active) {
            if ($user->id === $by->id) {
                throw new ValidationException('You cannot deactivate your own account.');
            }
            if ($user->hasRole('admin') && $this->isLastActiveAdmin($user)) {
                throw new ValidationException('The last active administrator cannot be deactivated.');
            }
        }

        $user->update(['is_active' => $active]);
        $this->audit->log($active ? 'staff.activated' : 'staff.deactivated',
            ($active ? 'Activated' : 'Deactivated')." staff account {$user->username}", $user, $by);

        return $user;
    }

    /** Generates a random temporary password; the user must change it at next login. */
    public function resetPassword(User $user, User $by): string
    {
        $temporary = Str::password(12, symbols: false);
        $user->update(['password' => $temporary, 'must_change_password' => true]);
        $this->audit->log('staff.password_reset', "Reset password of {$user->username}", $user, $by);

        return $temporary;
    }

    protected function isLastActiveAdmin(User $user): bool
    {
        return User::where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('slug', 'admin'))
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }
}
