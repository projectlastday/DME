<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'super_admin';
    }

    public function rules(): array
    {
        $studentId = $this->route('student')?->getKey();

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('user', 'nama')
                    ->ignore($studentId, 'id_user')
                    ->where(fn ($query) => $query->whereIn('id_role', [2, 3])),
            ],
        ];

        if ($this->isMethod('post')) {
            $rules['password'] = ['required', 'string'];
        }

        return $rules;
    }
}
