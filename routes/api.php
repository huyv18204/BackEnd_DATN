<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\DeliveryPersonController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailsController;
use App\Http\Controllers\OrderStatusHistoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductAttController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ShipmentDetailController;
use App\Http\Controllers\ShippingAddressController;
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

// client
Route::middleware('check.campaign')->group(function () {
    Route::get('v1/categories/', [CategoryController::class, 'index']);
    Route::get('v1/categories/{slug}', [CategoryController::class, 'getProductByCategory']);
    Route::get("v1/products", [ProductController::class, 'index']);
    Route::get("v1/products/{slug}", [ProductController::class, 'getBySlug']);
    Route::get('v1/products/{id}/productAtts', [ProductAttController::class, 'index']);
    Route::get("v1/sizes", [SizeController::class, 'index']);
    Route::get("v1/colors", [ColorController::class, 'index']);
});


Route::prefix("v1")->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,2');
    Route::post('register', [AuthController::class, 'register']);
    Route::get('/email/verify/{id}', [AuthController::class, 'verify'])->name('verification.verify');
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('password/email', [AuthController::class, 'sendResetOTPEmail'])->middleware('throttle:5,30');
    Route::post('password/reset', [AuthController::class, 'resetPasswordWithOTP']);
    Route::prefix('delivery-person')->group(function () {
        Route::post('register', [DeliveryPersonController::class, 'register']);
    });
    Route::middleware(['api', 'auth.jwt'])->prefix('auth')->as('auth.')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('profile', [AuthController::class, 'editProfile']);
        route::post('changePassword', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});


Route::prefix("v1")->middleware(['auth.jwt', 'auth.admin'])->group(function () {
    Route::prefix('categories')->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{id}/show', [CategoryController::class, 'show']);
        Route::put('/{id}/toggle-status', [CategoryController::class, 'toggleStatus']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    Route::prefix("products")->group(function () {
        Route::post("/", [ProductController::class, 'store']);
        Route::put("/{id}", [ProductController::class, 'update']);
        Route::get("/{id}/show", [ProductController::class, 'show']);
        Route::put("{id}/toggle-status", [ProductController::class, 'toggleStatus']);
        Route::delete("/{id}", [ProductController::class, 'destroy']);
        Route::post("/check-active", [ProductController::class, 'checkIsActive']);
    });

    Route::prefix('products/{product_id}/productAtts')->group(function () {
        Route::post('/', [ProductAttController::class, 'store']);
        Route::put('/{id}', [ProductAttController::class, 'update']);
        Route::delete('/{id}', [ProductAttController::class, 'destroy']);
    });


    Route::prefix("colors")->group(function () {
        Route::post("/", [ColorController::class, 'store']);
        Route::put("/{id}", [ColorController::class, 'update']);
        Route::put('/{id}/toggle-status', [ColorController::class, 'toggleStatus']);
        Route::delete("/{id}", [ColorController::class, 'destroy']);
    });

    Route::prefix("sizes")->group(function () {
        Route::post("/", [SizeController::class, 'store']);
        Route::put("/{id}", [SizeController::class, 'update']);
        Route::put('/{id}/toggle-status', [SizeController::class, 'toggleStatus']);
        Route::delete("/{id}", [SizeController::class, 'destroy']);
    });

    Route::prefix("orders")->group(function () {
        Route::get("/", [OrderController::class, 'index']);
        Route::get("{id}/delivery-person", [OrderController::class, 'getByDeliveryPersonId']);
        Route::get("{id}/delivery-person/history", [OrderController::class, 'historyDeliveredById']);
    });

    Route::prefix("users")->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}/role', [UserController::class, 'updateRole']);
        Route::get('/blacklist', [UserController::class, 'blackList']);
        Route::put('/{id}/toggle-blacklist', [UserController::class, 'toggleBlackList']);
    });

    Route::prefix("campaigns")->middleware('check.campaign')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::get('category', [CampaignController::class, 'category']);
        Route::get('filter', [CampaignController::class, 'filter']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::put('/{id}', [CampaignController::class, 'update']);
        Route::get('/{id}/show', [CampaignController::class, 'show']);
        Route::get('/{id}/status', [CampaignController::class, 'status']);
        Route::post('{id}/add-product', [CampaignController::class, 'addProduct']);
        route::delete('/{id}/product', [CampaignController::class, 'destroyMultiple']);
        Route::delete('{id}/product/{productId}', [CampaignController::class, 'destroy']);
        route::put('/{id}/toggle-status', [CampaignController::class, 'toggleStatus']);
    });

    Route::prefix("delivery-persons")->group(function () {
        Route::get("/", [DeliveryPersonController::class, 'index']);
        Route::post("/", [DeliveryPersonController::class, 'store']);
        Route::get("/account-waiting-confirm", [DeliveryPersonController::class, 'getAccountRegister']);
        Route::put("/{id}/confirm-account", [DeliveryPersonController::class, 'confirmAccount']);
    });


    Route::prefix("shipments")->group(function () {
        Route::get("/", [ShipmentController::class, 'index']);
        Route::post("/", [ShipmentController::class, 'store']);
        Route::put("/{id}", [ShipmentController::class, 'update']);
        Route::put('/{id}/status', [ShipmentController::class, 'updateStatus']);
        Route::get('/{id}/user', [ShipmentController::class, 'getByUserId']);
    });
});

Route::prefix("v1")->middleware(['auth.jwt'])->group(function () {

    Route::prefix("carts")->group(function () {
        Route::get("/", [CartController::class, 'show']);
        Route::post("/", [CartController::class, 'store']);
        Route::put("/{id}", [CartController::class, 'update']);
        Route::delete("/{id}", [CartController::class, 'destroy']);
    });


    Route::prefix("shipping-addresses")->group(function () {
        Route::post("/", [ShippingAddressController::class, 'store']);
        Route::put("/{id}", [ShippingAddressController::class, 'update']);
        Route::delete("/{id}", [ShippingAddressController::class, 'destroy']);
        Route::get("/user-id", [ShippingAddressController::class, 'getByUserId']);
        Route::get("/{id}", [ShippingAddressController::class, 'show']);
    });

    Route::prefix("orders")->group(function () {
        Route::post("/", [OrderController::class, 'store']);
        Route::get("/{id}", [OrderController::class, 'show'])->where("id", "[0-9]+");
        Route::get('/{id}/products', [OrderDetailsController::class, 'show']);
        Route::put("/{id}/order-status", [OrderController::class, 'updateOrderStt']);
        Route::put("/{id}/payment-status", [OrderController::class, 'updatePaymentStt']);
        Route::put("{id}/assign-delivery-person", [OrderController::class, 'assignToDeliveryPerson']);
        Route::put("/assign-many-delivery-person", [OrderController::class, 'assignManyToDeliveryPerson']);
        Route::get("delivery-person", [OrderController::class, 'getByDeliveryPersonLogin']);
        Route::put("on-delivery-status", [OrderController::class, 'updateManyOrderToOnDeliveryStatus']);
        Route::get("delivery-person/history", [OrderController::class, 'historyDelivered']);
    });


    Route::prefix("delivery-persons")->group(function () {
        Route::get("/{id}", [DeliveryPersonController::class, 'show'])->where("id", "[0-9]+");
        Route::put("/{id}", [DeliveryPersonController::class, 'update'])->where("id", "[0-9]+");
        Route::put("/{id}/status", [DeliveryPersonController::class, 'updateStatus']);
        //Viet them
        Route::put("/statusForShipper", [DeliveryPersonController::class, 'toggleStatusForShipper']);
        Route::get("/statusForShipper", [DeliveryPersonController::class, 'getStatusForShipper']);
    });

    Route::prefix("shipment-details")->group(function () {
        Route::get("/{shipment_id}", [ShipmentDetailController::class, 'show']);
    });

    Route::prefix("shipments")->group(function () {
        Route::get("/{id}", [ShipmentController::class, 'show'])->where("id", "[0-9]+");
        Route::put('/{id}/status', [ShipmentController::class, 'updateStatus']);
        Route::get('/user', [ShipmentController::class, 'getByUserLogin']);
    });

    Route::prefix("order-status-histories")->group(function () {
        Route::post("/", [OrderStatusHistoryController::class, 'store']);
    });

    Route::prefix('districts')->group(function () {
        Route::get("/", [DistrictController::class, 'index']);
        Route::get("/{code}", [DistrictController::class, 'show']);
        Route::put("/{id}/fee-delivery", [DistrictController::class, 'updateDeliveryFee']);
    });

    Route::prefix('provinces')->group(function () {
        Route::get("/", [ProvinceController::class, 'index']);
    });
});

Route::prefix('v1')->group(function () {
    Route::post('/momo/payment', [PaymentController::class, 'createPayment']);
    Route::post('/payment/callback', [PaymentController::class, 'handlePaymentCallback']);
});
