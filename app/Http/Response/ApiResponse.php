<?php

namespace App\Http\Response;

class ApiResponse
{
    public static function data($data, $status)
    {
        return response()->json([$data], $status);
    }

    public static function message(string $message, $status)
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }

    public static function error(string $message, $status)
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }
}
