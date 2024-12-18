<?php

namespace App\Http\Controllers;

use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WardController extends Controller
{
    public function index() : JsonResponse
    {
        $wards = Ward::all();
        return response()->json($wards);
    }
}
