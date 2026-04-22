<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetToken;
use App\Models\TwoFactorCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use App\Models\Role;

class AuthController extends Controller
{
    // TODO: create special response object structure for better exp for frontend (special error codes, etc.)

    // registration
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()],
        ]);

        $studentRole = Role::where('name', 'student')->first();

        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => $request['password'],
            'role_id' => $studentRole->id,
            'email_verified_token' => Str::random(64),
        ]);

        Mail::to($user->email)->send(new \App\Mail\VerifyEmail($user));

        return response()->json([
            'message' => 'Проверьте email для подтверждения регистрации!',
        ], 201);
    }

    // email verification
    public function verifyEmail(string $token): JsonResponse
    {
        $user = User::where('email_verified_token', $token)->firstOrFail();

        $user->update([
            'email_verified_token' => null,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Email подтвержден!',
        ]);
    }

    // login
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::with('role')->where('email', $request->email)->first();

        // checking existence and password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Неверный пароль или email.',
            ], 401);
        }

        // checking email verification status
        if (!$user->isVerified()) {
            return response()->json([
                'message' => 'Подтвердите email перед входом.',
            ], 403);
        }

        // 2fa for admins and moderators
        if ($user->hasAnyRole(['moderator', 'admin'])) {
            $this->sendTwoFactorCode($user);

            return response()->json([
                'message' => 'Код для подтверждения входа отправлен на почту!',
                'requires_2fa' => true,
                'user_id' => $user->id,
            ]);
        }

        return $this->issueSession($user);
    }

    // 2fa verification
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'code' => ['required', 'string'],
        ]);

        $user = User::findOrFail($request->user_id);

        $twoFactorCode = TwoFactorCode::where('user_id', $user->id)
            ->where('code', $request->code)
            ->latest()
            ->first();

        if (!$twoFactorCode || $twoFactorCode->isExpired()) {
            return response()->json([
                'message' => 'Код неверен или время его действия истекло',
            ], 401);
        }

        $twoFactorCode->delete();

        return $this->issueSession($user);
    }

    // logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Вы вышли из системы',
        ])->withCookie('laravel_session');
    }

    // forgot password
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Важно: всегда возвращаем одинаковый ответ
        // чтобы нельзя было узнать существует ли email в базе
        if (!$user) {
            return response()->json([
                'message' => 'Если этот email зарегистрирован, вы получите письмо с инструкциями.',
            ]);
        }

        // Удаляем старые токены этого пользователя
        PasswordResetToken::where('user_id', $user->id)->delete();

        $token = Str::random(64);

        PasswordResetToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addMinutes(60),
        ]);

        Mail::to($user->email)->send(new \App\Mail\ResetPassword($user, $token));

        return response()->json([
            'message' => 'Если этот email зарегистрирован, вы получите письмо с инструкциями.',
        ]);
    }

    // password reset
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()],
        ]);

        $resetToken = PasswordResetToken::where('token', $request->token)->first();

        if (!$resetToken || $resetToken->isExpired()) {
            return response()->json([
                'message' => 'Ссылка для сброса пароля недействительна или истекла.',
            ], 422);
        }

        $user = User::findOrFail($resetToken->user_id);

        // checking that new password don't match an old one
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Новый пароль не должен совпадать с текущим.',
            ], 422);
        }

        $user->update([
            'password' => $request->password,
        ]);

        $resetToken->delete();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Пароль успешно изменён. Войдите с новым паролем.',
        ]);
    }

    // current user
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('role'));
    }

    // send two-factor code
    private function sendTwoFactorCode(User $user): void
    {
        TwoFactorCode::where('user_id', $user->id)->delete();

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        TwoFactorCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new \App\Mail\TwoFactorCode($user, $code));
    }

    // issuing session
    private function issueSession(User $user): JsonResponse
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Успешный вход.',
            'token'   => $token,
            'user'    => $user->load('role'),
        ]);
    }
}
