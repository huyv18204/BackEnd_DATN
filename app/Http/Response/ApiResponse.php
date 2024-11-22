<?php

namespace App\Http\Response;

class ApiResponse
{
    public static function data($data, $status = 200)
    {
        return response()->json($data, $status);
    }

    public static function message(string $message, $status = 200)
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }

    public static function error(string $message, $status = 500)
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }
}
