<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\Password\ChangePasswordController;
use App\Http\Controllers\Auth\Password\ForgotPasswordController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('login', [LoginController::class, 'login'])->name('login');
Route::post('register', [RegisterController::class, 'register'])->name('register');
Route::post('refresh', [LoginController::class, 'refresh'])->name('refresh');
Route::middleware(['api', 'auth.jwt'])->prefix('auth')->as('auth.')->group(function () {
    Route::get('profile', [ProfileController::class, 'profile']);
    Route::post('profile', [ProfileController::class, 'editProfile']);
    route::post('changePassword', [ChangePasswordController::class, 'changePassword']);
    Route::get('logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::post('password/reset', [ForgotPasswordController::class, 'resetPassword']);
});
