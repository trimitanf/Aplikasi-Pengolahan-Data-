<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Nama wajib diisi.',
            'password.required' => 'NIP wajib diisi.',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $inputIdentity = trim((string) $this->input('username'));
        $password = (string) $this->input('password');

        // Cari pengguna berdasarkan username atau nama lengkap (case-insensitive)
        $matchedUser = User::whereRaw('LOWER(username) = ?', [Str::lower($inputIdentity)])
            ->orWhereRaw('LOWER(name) = ?', [Str::lower($inputIdentity)])
            ->first();

        $loginUsername = $matchedUser ? $matchedUser->username : $inputIdentity;

        $credentials = [
            'username' => $loginUsername,
            'password' => $password,
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 60);

            // Check if user exists but inactive
            if ($matchedUser && ! $matchedUser->is_active) {
                throw ValidationException::withMessages([
                    'username' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi pengelola sistem.',
                ]);
            }

            throw ValidationException::withMessages([
                'username' => 'Nama atau NIP yang Anda masukkan salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
