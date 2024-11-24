<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function index() : JsonResponse
    {
        $districts = District::all();
        return response()->json($districts);
    }
}
