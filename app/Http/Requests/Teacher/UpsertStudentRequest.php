<?php

namespace App\Http\Requests\Teacher;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_TEACHER) ?? false;
    }

    public function rules(): array
    {
        $studentId = $this->route('student')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('user', 'nama')
                    ->ignore($studentId, 'id_user')
                    ->where(fn ($query) => $query->whereIn('id_role', [
                        \App\Models\Role::idForName(User::ROLE_TEACHER),
                        \App\Models\Role::idForName(User::ROLE_STUDENT),
                    ])),
            ],
        ];
    }
}
