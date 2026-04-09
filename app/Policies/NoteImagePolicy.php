<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\NoteImage;
use App\Models\User;

class NoteImagePolicy
{
    public function view(User $user, NoteImage $noteImage): bool
    {
        return (new NotePolicy())->view($user, $noteImage->note);
    }

    public function create(User $user, Note $note): bool
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

    public function delete(User $user, NoteImage $noteImage): bool
    {
        if ($user->hasRole(User::ROLE_SUPER_ADMIN)) {
            return true;
        }

        return $this->create($user, $noteImage->note);
    }
}
