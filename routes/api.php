<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductAttController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\UserController;
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
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::get('/email/verify/{id}', [AuthController::class, 'verify'])->name('verification.verify');
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('password/email', [AuthController::class, 'sendResetOTPEmail'])->middleware('throttle:3,30');
    Route::post('password/reset', [AuthController::class, 'resetPasswordWithOTP']);
    Route::middleware(['api', 'auth.jwt'])->prefix('auth')->as('auth.')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('profile', [AuthController::class, 'editProfile']);
        route::post('changePassword', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::get('v1/categories', [CategoryController::class, 'index']);
Route::get("v1/products", [ProductController::class, 'index']);
Route::get('v1/products/2/productAtts', [ProductAttController::class, 'index']);

Route::prefix("v1")->middleware('auth.jwt', 'auth.admin')->group(function () {
    Route::prefix('categories')->group(function () {
        Route::put('/{id}/restore', [CategoryController::class, 'restore']);
        Route::get('/trash', [CategoryController::class, 'trash']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::get('/{slug}', [CategoryController::class, 'getBySlug']);
        Route::get('/{id}/show', [CategoryController::class, 'show']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    Route::prefix("products")->group(function () {
        Route::put('/{id}/restore', [ProductController::class, 'restore']);
        Route::post("/", [ProductController::class, 'store']);
        Route::put("/{id}", [ProductController::class, 'update']);
        Route::get('/trash', [ProductController::class, 'trash']);
        Route::get("/{slug}", [ProductController::class, 'getBySlug']);
        Route::get("/{id}/show", [ProductController::class, 'show']);
        Route::get("/{id}/ProductAtts", [ProductController::class, 'getProductAtts']);
        Route::delete("/{id}", [ProductController::class, 'destroy']);
    });
    Route::prefix('products/{product_id}/productAtts')->group(function () {
        Route::post('/', [ProductAttController::class, 'store']);
        Route::put('/{id}', [ProductAttController::class, 'update']);
        Route::delete('/{id}', [ProductAttController::class, 'destroy']);
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

    Route::prefix("orders")->group(function () {
        Route::get("/", [OrderController::class, 'index']);
        Route::post("/", [OrderController::class, 'store']);
        Route::put("/{id}/order-status", [OrderController::class, 'updateOrderStt']);
        Route::put("/{id}/payment-status", [OrderController::class, 'updatePaymentStt']);
    });

    Route::prefix("users")->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}/role', [UserController::class, 'updateRole']);
        Route::get('/blacklist', [UserController::class, 'blackList']);
        Route::delete('/{id}/add-blacklist', [UserController::class, 'addBlackList']);
        Route::put('/{id}/restore-blacklist', [UserController::class, 'restoreBlackList']);
    });
});
