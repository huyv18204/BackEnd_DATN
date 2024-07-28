<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return $next($request);
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token đã hết hạn'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token không hợp lệ'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token không được cung cấp'], 401);
        } catch (\Exception $exception) {
            return response()->json(['message' => 'Lỗi không xác định', 'error' => $exception->getMessage()], 500);
        }
    }
}
