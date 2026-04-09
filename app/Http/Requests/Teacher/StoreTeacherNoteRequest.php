<?php

namespace App\Http\Requests\Teacher;

use App\Http\Requests\Shared\UpsertNoteRequest;
use App\Models\User;

class StoreTeacherNoteRequest extends UpsertNoteRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_TEACHER) ?? false;
    }
}
