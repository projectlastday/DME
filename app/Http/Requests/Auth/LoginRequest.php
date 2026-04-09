<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $login = trim((string) $this->string('login'));

        $user = User::query()->where('nama', $login)->first();

        if ($user !== null && (string) $user->password === (string) $this->input('password')) {
            Auth::login($user, false);
            return;
        }

        throw ValidationException::withMessages([
            'login' => __('auth.failed'),
        ]);
    }
}
