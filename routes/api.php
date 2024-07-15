<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix("v1")->group(function () {
    Route::prefix("products")->group(function () {
        Route::get("/", [ProductController::class, 'index']);
        Route::post("/", [ProductController::class, 'store']);
        Route::put("/{id}", [ProductController::class, 'update']);
        Route::get("/{id}", [ProductController::class, 'show']);
        Route::delete("/{id}", [ProductController::class, 'destroy']);
    });

    Route::prefix("customers")->group(function () {
        Route::get("/", [CustomerController::class, 'index']);
        Route::post("/", [CustomerController::class, 'store']);
        Route::put("/{id}", [CustomerController::class, 'update']);
        Route::get("/{id}", [CustomerController::class, 'show']);
        Route::delete("/{id}", [CustomerController::class, 'destroy']);
    });
});

