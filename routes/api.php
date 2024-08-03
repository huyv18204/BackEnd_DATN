<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\ProductAttController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SizeController;
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
    Route::prefix('categories')->group(function () {
        Route::put('/{id}/restore', [CategoryController::class, 'restore']);
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/trash', [CategoryController::class, 'trash']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::get('/{slug}', [CategoryController::class, 'getBySlug']);
        Route::get('/{id}/show', [CategoryController::class, 'show']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });
    Route::prefix("products")->group(function () {
        Route::put('/{id}/restore', [ProductController::class, 'restore']);
        Route::get("/", [ProductController::class, 'index']);
        Route::post("/", [ProductController::class, 'store']);
        Route::put("/{id}", [ProductController::class, 'update']);
        Route::get('/trash', [ProductController::class, 'trash']);
        Route::get("/{slug}", [ProductController::class, 'getBySlug']);
        Route::get("/{id}/show", [ProductController::class, 'show']);
        Route::delete("/{id}", [ProductController::class, 'destroy']);

    });
    Route::prefix('products/{product_id}/productAtts')->group(function () {
        Route::get('/', [ProductAttController::class, 'index']);
        Route::post('/', [ProductAttController::class, 'store']);
        Route::put('/{id}/size/{size_id}', [ProductAttController::class, 'update']);
        Route::delete('/{id}/size/{size_id}', [ProductAttController::class, 'destroy']);
        Route::get("/{id}/size/{size_id}/show", [ProductAttController::class, 'show']);
    });


    Route::prefix("colors")->group(function () {
        Route::put('/{id}/restore', [ColorController::class, 'restore']);
        Route::get('/trash', [ColorController::class, 'trash']);
        Route::get("/", [ColorController::class, 'index']);
        Route::post("/", [ColorController::class, 'store']);
        Route::put("/{id}", [ColorController::class, 'update']);
        Route::get("/{id}", [ColorController::class, 'show']);
        Route::delete("/{id}", [ColorController::class, 'destroy']);
    });

    Route::prefix("sizes")->group(function () {
        Route::put('/{id}/restore', [SizeController::class, 'restore']);
        Route::get('/trash', [SizeController::class, 'trash']);
        Route::get("/", [SizeController::class, 'index']);
        Route::post("/", [SizeController::class, 'store']);
        Route::put("/{id}", [SizeController::class, 'update']);
        Route::get("/{id}", [SizeController::class, 'show']);
        Route::delete("/{id}", [SizeController::class, 'destroy']);
    });
});
