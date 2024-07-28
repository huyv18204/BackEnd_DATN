<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Tài khoản hoặc mật khẩu không đúng',
            ], 401);
        }

        $data = [
            'user_id' => auth('api')->user()->id,
            'random' => Str::random(32) . time(),
            'exp' => time() + config('jwt.refresh_ttl') * 60,
        ];

        $refreshToken = JWTAuth::getJWTProvider()->encode($data);
        return response()->json([
            'message' => 'Đăng nhập thành công',
            'access_token' => $token,
            'refresh_token' => $refreshToken,
        ], 200);
    }


    public function refresh(Request $request)
    {
        $refreshToken = $request->refresh_token;

        try {
            $decoded = JWTAuth::getJWTProvider()->decode($refreshToken);

            if ($decoded['exp'] < time()) {
                return response()->json(['error' => 'Refresh token đã hết hạn'], 401);
            }

            $user = User::find($decoded['user_id']);
            if (!$user) {
                return response()->json(['error' => 'Tài khoản không tồn tại'], 404);
            }

            $currentAccessToken = JWTAuth::getToken();
            if ($currentAccessToken) {
                JWTAuth::setToken($currentAccessToken)->invalidate();
            }

            $newAccessToken = auth('api')->login($user);
            return response()->json([
                'access_token' => $newAccessToken
            ], 200);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Refresh token đã hết hạn'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'refresh token không hợp lệ'], 400);
        }
    }


    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Đăng xuất thành công']);
    }
}
