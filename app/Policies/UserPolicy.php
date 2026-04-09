<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_SUPER_ADMIN);
    }

    public function view(User $user, User $subject): bool
    {
        return $user->hasRole(User::ROLE_SUPER_ADMIN) && $this->isManageableRole($subject);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_SUPER_ADMIN);
    }

    public function update(User $user, User $subject): bool
    {
        return $user->hasRole(User::ROLE_SUPER_ADMIN) && $this->isManageableRole($subject);
    }

    public function delete(User $user, User $subject): bool
    {
        return $user->hasRole(User::ROLE_SUPER_ADMIN) && $this->isManageableRole($subject);
    }

    public function resetPassword(User $user, User $subject): bool
    {
        return $user->hasRole(User::ROLE_SUPER_ADMIN) && $this->isManageableRole($subject);
    }

    private function isManageableRole(User $subject): bool
    {
        return $subject->hasRole(User::ROLE_TEACHER, User::ROLE_STUDENT);
    }
}
