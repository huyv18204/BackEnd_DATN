<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ProfileRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Jobs\SendPasswordResetEmail;
use App\Jobs\SendVerifyEmail;
use App\Models\RefreshToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Tài khoản không tồn tại.'], 404);
        }

        if ($user->is_blocked) {
            return response()->json(['message' => 'Tài khoản của bạn đã bị khóa.'], 403);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Tài khoản của bạn chưa được xác thực. Vui lòng kiểm tra email để xác thực tài khoản.'], 403);
        }

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Tài khoản hoặc mật khẩu không đúng'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'refresh_token' => $this->generateRefreshToken($user->id),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ], 200);
    }


    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token');

        try {
            $storedToken = RefreshToken::where('token', $refreshToken)->first();

            if (!$storedToken) {
                return response()->json(['error' => 'Refresh token không hợp lệ'], 401);
            }

            if (Carbon::now()->greaterThan($storedToken->expires_at)) {
                $storedToken->delete();
                return response()->json(['error' => 'Refresh token đã hết hạn'], 400);
            }

            $user = User::find($storedToken->user_id);
            if (!$user) {
                return response()->json(['error' => 'Tài khoản không tồn tại'], 404);
            }

            $storedToken->delete();

            $newAccessToken = auth('api')->login($user);
            $newRefreshToken = $this->generateRefreshToken($user->id);

            return response()->json([
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
            ], 200);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Refresh token không hợp lệ'], 400);
        }
    }


    public function logout()
    {
        $user = auth()->user();
        if ($user) {
            RefreshToken::where('user_id', $user->id)->delete();
        }

        auth()->logout();
        return response()->json(['message' => 'Đăng xuất thành công']);
    }

    private function generateRefreshToken($userId)
    {
        RefreshToken::where('user_id', $userId)->delete();
        $refreshToken = Str::random(60);
        $expiresAt = Carbon::now()->addMinutes(config('jwt.refresh_ttl'));

        RefreshToken::create([
            'user_id' => $userId,
            'token' => $refreshToken,
            'expires_at' => $expiresAt,
        ]);

        return $refreshToken;
    }

    public function register(RegisterRequest $request)
    {
        $credentials = $request->validated();

        $user = User::query()->create($credentials);

        $verificationUrl = $this->generateVerificationUrl($user);

        SendVerifyEmail::dispatch($user, $verificationUrl);

        return response()->json([
            'message' => 'Đăng ký thành công. Vui lòng kiểm tra email để xác thực tài khoản.',
        ], 201);
    }

    public function verify(Request $request, $id)
    {
        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Liên kết xác thực không hợp lệ hoặc đã hết hạn.'], 401);
        }

        $user = User::findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Tài khoản apiđã được xác thực trước đó.'], 200);
        }

        $user->markEmailAsVerified();
        
        return redirect(env('FRONTEND_URL_LOGIN'));
    }

    protected function generateVerificationUrl(User $user)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id]
        );
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Mật khẩu hiện tại không đúng'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Đổi mật khẩu thành công'], 200);
    }


    public function sendResetOTPEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $email = $request->email;
        $currentTime = Carbon::now();
        $minutes = config('auth.passwords.users.expire');

        $resetRequest = DB::table('password_reset_requests')->where('email', $email)->first();

        if ($resetRequest) {
            $expirationTime = Carbon::parse($resetRequest->last_request_at)->addMinutes(30);

            if ($currentTime->lessThan($expirationTime)) {
                if ($resetRequest->request_count >= 3) {
                    return response()->json(['message' => 'Vui lòng chờ trước khi gửi yêu cầu mới.'], 429);
                }
            } else {
                $resetRequest->request_count = 0;
            }
        } else {
            $resetRequest = (object)[
                'request_count' => 0,
                'last_request_at' => null,
            ];
        }

        $otp = random_int(100000, 999999);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($otp), 'created_at' => $currentTime]
        );

        DB::table('password_reset_requests')->updateOrInsert(
            ['email' => $email],
            [
                'request_count' => $resetRequest->request_count + 1,
                'last_request_at' => $currentTime,
            ]
        );

        SendPasswordResetEmail::dispatch($otp, $email, $minutes);

        return response()->json(['message' => 'Mã OTP đã được gửi đến email của bạn.'], 201);
    }


    public function resetPasswordWithOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $email = $request->email;
        $user = User::where('email', $email)->first();
        $otp = $request->otp;
        $password = $request->password;
        if (Hash::check($password, $user->password)) {
            return response()->json(['error' => 'Mật khẩu mới không được trùng với mật khẩu cũ.'], 422);
        }


        $passwordReset = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$passwordReset) {
            return response()->json(['message' => 'Mã OTP không hợp lệ.'], 422);
        }

        $expirationTime = Carbon::parse($passwordReset->created_at)->addMinutes(config('auth.passwords.users.expire'));

        if (Carbon::now()->greaterThan($expirationTime)) {
            return response()->json(['message' => 'Mã OTP đã hết hạn.'], 422);
        }

        if (!Hash::check($otp, $passwordReset->token)) {
            return response()->json(['message' => 'Mã OTP không hợp lệ.'], 422);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->password = Hash::make($password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        DB::table('password_reset_requests')->where('email', $email)->delete();

        return response()->json(['message' => 'Mật khẩu đã được đặt lại thành công.']);
    }


    public function profile()
    {
        $user = auth()->user();
        return response()->json(['data' => $user], 200);
    }

    public function editProfile(ProfileRequest $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Người dùng không được xác thực'], 401);
        }
        $data = $request->all();
        $user->update($data);
        return response()->json(['message' => 'Profile cập nhật thành công', 'data' => $user], 200);
    }
}
