<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function adminLogin(Request $request)
    {
        $user = $this->attempt($request);

        if (! $user->role->isEmployee()) {
            throw ValidationException::withMessages([
                'login' => 'Водитель не может входить в web-админку.',
            ]);
        }

        return $this->tokenResponse($user, 'admin-panel');
    }

    public function driverLogin(Request $request)
    {
        $user = $this->attempt($request);

        if ($user->role !== UserRole::Driver) {
            throw ValidationException::withMessages([
                'login' => 'Сотрудник не может входить в мобильное приложение водителя.',
            ]);
        }

        return $this->tokenResponse($user, 'mobile-driver-app');
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Сессия завершена.']);
    }

    private function attempt(Request $request): User
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('login', $credentials['login'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => 'Неверный логин или пароль.',
            ]);
        }

        $user->update(['last_login_at' => now()]);

        return $user;
    }

    private function tokenResponse(User $user, string $deviceName)
    {
        return response()->json([
            'token' => $user->createToken($deviceName)->plainTextToken,
            'user' => $user,
        ]);
    }
}

