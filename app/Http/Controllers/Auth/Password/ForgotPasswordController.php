<?php

namespace App\Http\Controllers\Auth\Password;

use App\Http\Controllers\Controller;
use App\Mail\verifyEmail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $email = $request->email;

        if ($email !== $user->email) {
            return response()->json(['message' => 'Bạn không có quyền gửi yêu cầu đặt lại mật khẩu cho email này.'], 403);
        }
        $cacheKey = $email;
        $cacheTTL = 3;
        $maxAttempts = 3;

        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            return response()->json(['message' => 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau 3 phút.'], 429);
        }

        $token = Str::random(60);
        $minutes = config('auth.passwords.users.expire');

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );

        Mail::to($email)->send(new VerifyEmail($token, $email, $minutes));

        Cache::put($cacheKey, $attempts + 1, now()->addMinutes($cacheTTL));

        return response()->json(['message' => 'Liên kết đặt lại mật khẩu đã được gửi đến email của bạn.']);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $user = auth()->user();
        $email = $request->email;
        if ($email != $user->email) {
            return response()->json(['message' => 'Bạn không có quyền đặt lại mật khẩu cho email này']);
        }
        $token = $request->token;
        $password = $request->password;

        $passwordReset = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$passwordReset || !Hash::check($token, $passwordReset->token)) {
            return response()->json(['message' => 'Token không hợp lệ hoặc đã hết hạn.'], 422);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->password = Hash::make($password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json(['message' => 'Mật khẩu đã được đặt lại thành công.']);
    }
}
