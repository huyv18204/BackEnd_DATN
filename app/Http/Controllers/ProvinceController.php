<?php

namespace App\Http\Controllers;

use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function index() : JsonResponse
    {
        $province = Province::all()->first();
        return response()->json($province);
    }
}
