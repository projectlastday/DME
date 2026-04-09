<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\Shared\UpsertNoteRequest;
use App\Models\User;

class UpsertStudentNoteRequest extends UpsertNoteRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_STUDENT) ?? false;
    }
}
