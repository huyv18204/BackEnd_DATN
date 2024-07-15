<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductVariantController;
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
    Route::prefix('category')->group(function () {
        Route::get('', [CategoryController::class, 'index']);
        Route::post('', [CategoryController::class, 'store']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });
    Route::prefix("products")->group(function () {
        Route::get("/", [ProductController::class, 'index']);
        Route::post("/", [ProductController::class, 'store']);
        Route::put("/{id}", [ProductController::class, 'update']);
        Route::get("/{id}", [ProductController::class, 'show']);
        Route::delete("/{id}", [ProductController::class, 'destroy']);
    });
    Route::prefix('products/{id}')->group(function () {
        Route::get('productVariant', [ProductVariantController::class, 'show']);
        Route::post('productVariant', [ProductVariantController::class, 'store']);
        Route::put('productVariant/{variant_id}', [ProductVariantController::class, 'update']);
        Route::delete('productVariant/{variant_id}', [ProductVariantController::class, 'destroy']);
    });
});
