<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_SUPER_ADMIN, User::ROLE_TEACHER, User::ROLE_STUDENT);
    }

    public function view(User $user, Note $note): bool
    {
        if ($user->hasRole(User::ROLE_SUPER_ADMIN, User::ROLE_TEACHER)) {
            return true;
        }

        if (! $user->hasRole(User::ROLE_STUDENT) || $note->student_id !== $user->getKey()) {
            return false;
        }

        return $note->author_id === $user->getKey()
            || User::roleMatches($note->author_role_snapshot, User::ROLE_TEACHER)
            || User::roleMatches($note->author_role_snapshot, User::ROLE_SUPER_ADMIN);
    }

    public function create(User $user, User $student): bool
    {
        if (! $student->hasRole(User::ROLE_STUDENT)) {
            return false;
        }

        return match ($user->role) {
            User::ROLE_TEACHER => true,
            User::ROLE_STUDENT => $user->getKey() === $student->getKey(),
            default => false,
        };
    }

    public function update(User $user, Note $note): bool
    {
        return match ($user->role) {
            User::ROLE_TEACHER => $note->author_id === $user->getKey()
                && User::roleMatches($note->author_role_snapshot, User::ROLE_TEACHER),
            User::ROLE_STUDENT => $note->student_id === $user->getKey()
                && $note->author_id === $user->getKey()
                && User::roleMatches($note->author_role_snapshot, User::ROLE_STUDENT),
            default => false,
        };
    }

    public function delete(User $user, Note $note): bool
    {
        if ($user->hasRole(User::ROLE_SUPER_ADMIN)) {
            return true;
        }

        return $this->update($user, $note);
    }
}
