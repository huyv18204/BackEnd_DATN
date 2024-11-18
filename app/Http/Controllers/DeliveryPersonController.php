<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeliveryPerson\StoreRequest;
use App\Models\DeliveryPerson;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Psy\Util\Json;

class DeliveryPersonController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $query = DeliveryPerson::query()->with('user', 'vehicle');

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
                'status' => 'available',
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


    public function update(StoreRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {

            $delivery_person = DeliveryPerson::query()->find($id);

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
}
