<?php

namespace App\Http\Controllers\Auth\Password;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChangePasswordController extends Controller
{
    public function changePassword(Request $request)
    {
            $user = auth()->user();
            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'password' => 'required|confirmed|min:8'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Mật khẩu hiện tại không đúng'], 400);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json(['message' => 'Đổi mật khẩu thành công'], 200);
      
    }
}
