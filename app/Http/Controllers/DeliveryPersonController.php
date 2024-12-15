<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\DeliveryPerson\StoreRequest;
use App\Http\Requests\DeliveryPerson\UpdateRequest;
use App\Jobs\SendConfirmAccountInfo;
use App\Models\DeliveryPerson;
use App\Models\Order;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Psy\Util\Json;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeliveryPersonController extends Controller
{

    public function index(Request $request): JsonResponse
    {

        $sort = $request->input('sort', 'ASC');

        $query = DeliveryPerson::query()->with('user', 'vehicle')->orderBy('id', $sort);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('name')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('name') . '%');
            });
        }

        if ($request->has('email')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('email', $request->input('email'));
            });
        }

        if ($request->has('phone')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('phone', $request->input('phone'));
            });
        }

        if ($request->has('vehicle_name')) {
            $query->whereHas('vehicle', function ($q) use ($request) {
                $q->where('vehicle_name', 'like', '%' . $request->input('vehicle_name') . '%');
            });
        }

        if ($request->has('license_plate')) {
            $query->whereHas('vehicle', function ($q) use ($request) {
                $q->where('license_plate', $request->input('license_plate'));
            });
        }


        $delivery_persons = $request->input('size') ? $query->paginate($request->input('size')) : $query->get();

        return response()->json($delivery_persons);
    }

    public function show($id): JsonResponse
    {
        return response()->json(DeliveryPerson::with('user', 'vehicle')->find($id));
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validate = $request->validate([
            'status' => 'required|in:online,offline,on delivery',
        ], [
            'status.required' => 'Trạng thái không được để trống.',
            'status.in' => "Trạng thái không hợp lệ"
        ]);

        try {
            DeliveryPerson::query()->find($id)->update([
                'status' => $validate['status'],
            ]);
            return response()->json([
                "message" => "Cập nhật trạng thái thành công"
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $user = User::query()->create([
                'name' => $validated['personal']['name'],
                'email' => $validated['personal']['email'],
                'phone' => $validated['personal']['phone_number'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'address' => $validated['personal']['address'],
                'role' => 'shipper'
            ]);

            $vehicle = Vehicle::query()->create($validated['vehicle']);

            DeliveryPerson::query()->create([
                'status' => 'offline',
                'vehicle_id' => $vehicle->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => "Tạo người giao hàng thành công",
            ], 200);
        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json([
                'message' => "Lỗi: " . $exception->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {

            $delivery_person = DeliveryPerson::query()->find($id);
            if (User::query()->where('email', $validated['personal']['email'])->where('id', '!=', $delivery_person->user_id)->exists()) {
                return response()->json([
                    "message" => "Email đã tồn tại"
                ], 422);
            }

            if (User::query()->where('phone', $validated['personal']['phone_number'])->where('id', '!=', $delivery_person->user_id)->exists()) {
                return response()->json([
                    "message" => "Số điện thoại đã tồn tại"
                ], 422);
            }
            if ($delivery_person) {
                User::query()->find($delivery_person->user_id)->update([
                    'name' => $validated['personal']['name'],
                    'email' => $validated['personal']['email'],
                    'phone' => $validated['personal']['phone_number'],
                    'address' => $validated['personal']['address'],
                ]);

                Vehicle::query()->find($delivery_person->vehicle_id)->update($validated['vehicle']);
            }
            DB::commit();

            return response()->json([
                'message' => "Cập nhật người giao hàng thành công",
            ], 200);
        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json([
                'message' => "Lỗi: " . $exception->getMessage(),
            ], 500);
        }
    }

    public function register(\App\Http\Requests\DeliveryPerson\RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            $user = User::query()->create([
                'name' => $validated['personal']['name'],
                'email' => $validated['personal']['email'],
                'phone' => $validated['personal']['phone_number'],
                'password' => Hash::make($validated['personal']['password']),
                'address' => $validated['personal']['address'],
                'role' => 'shipper'
            ]);

            $vehicle = Vehicle::query()->create($validated['vehicle']);

            DeliveryPerson::query()->create([
                'status' => 'offline',
                'vehicle_id' => $vehicle->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => "Đăng kí thành công. Vui lòng kiểm tra mail xác nhận trong 2 - 3 ngày tới",
            ], 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => "Lỗi: " . $exception->getMessage(),
            ], 500);
        }
    }

    public function getAccountRegister(Request $request): JsonResponse
    {
        try {
            $query = User::query()->where('role', 'shipper')->where('email_verified_at', null)->orderByDesc('id');
            $delivery_person = $request->has('size') ? $query->paginate($request->input('size')) : $query->get();
            return response()->json($delivery_person);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function confirmAccount(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'string|in:accept,reject',
        ], [
            'type.in' => "Trạng thái không hợp lệ"
        ]);

        $user = User::query()->find($id);
        if (!$user) {
            return response()->json([
                'message' => "Tài khoản không tồn tại"
            ]);
        }
        if ($user->email_verified_at !== null) {
            return response()->json([
                'message' => "Tài khoản đã được xác thực trước đó"
            ]);
        }
        DB::beginTransaction();
        try {
            if ($validated['type'] === 'accept') {

                $user->update([
                    'email_verified_at' => now(),
                ]);
                SendConfirmAccountInfo::dispatch($user->email, $validated['type']);

                DB::commit();
                return response()->json([
                    'message' => "Tài khoản đã được xác nhận"
                ]);
            } else {
                $delivery_person = DeliveryPerson::query()->where('user_id', $id)->first();
                $delivery_person->delete();
                Vehicle::query()->find($delivery_person->vehicle_id)->delete();
                $user->delete();
                SendConfirmAccountInfo::dispatch($user->email, $validated['type']);
                DB::commit();
                return response()->json([
                    'message' => "Tài khoản đã được từ chối"
                ]);
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }
    //Viet them
    public function toggleStatusForShipper(Request $request): JsonResponse
    {
        $validate = $request->validate([
            'status' => 'required|in:online,offline,on delivery',
        ], [
            'status.required' => 'Trạng thái không được để trống.',
            'status.in' => "Trạng thái không hợp lệ",
        ]);

        try {
            $userId = JWTAuth::parseToken()->authenticate()->id;

            $deliveryPerson = DeliveryPerson::where('user_id', $userId)->first();

            if (!$deliveryPerson) {
                return response()->json([
                    'message' => 'Không tìm thấy người giao hàng tương ứng.'
                ], 404);
            }

            $deliveryPerson->update([
                'status' => $validate['status'],
            ]);

            return response()->json([
                "message" => "Cập nhật trạng thái thành công",
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function getStatusForShipper(Request $request): JsonResponse
    {
        try {
            $userId = JWTAuth::parseToken()->authenticate()->id;

            $deliveryPerson = DeliveryPerson::where('user_id', $userId)->first();

            if (!$deliveryPerson) {
                return response()->json([
                    'message' => 'Không tìm thấy người giao hàng tương ứng.'
                ], 404);
            }

            return response()->json([$deliveryPerson->status], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
