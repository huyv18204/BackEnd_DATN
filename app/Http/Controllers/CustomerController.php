<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $phone = $request->query('phone');
        $address = $request->query('address');
        $name = $request->query('name');
        $sort = $request->query('sort', 'ASC');
        $email = $request->query('email');
        $perPage = $request->query('perPage', 5);
        $customers = Customer::query();
        if ($phone) {
            $customers->where('phone', $phone);
        }
        if ($name) {
            $customers->where('name', 'like', '%' . $name . '%');
        }
        if ($address) {
            $customers->where('address', 'like', '%' . $address . '%');
        }
        if ($email) {
            $customers->where('email', $email);
        }
        $customers->orderBy('id', $sort);
        $customers = $customers->paginate($perPage);

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'password' => 'required|string|max:255',
        ]);

        $imageFile = $request->file("image");
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $url = strtolower(Str::random(10)) . time() . "." . $extension;
            $data['image'] = $url;
        }
        $data['password'] = Hash::make($request->password);
        $customer = Customer::query()->create($data);
        if ($customer) {
            return response()->json("Thêm thành công");
        } else {
            return response()->json("Thêm thất bại", 400);
        }
    }

    public function show($id)
    {
        $customer = Customer::query()->findOrFail($id);
        return response()->json($customer);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
//            'image' => 'nullable|string|max:255',
            'email' => 'required|email|unique:customers,email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
//            'password' => 'required|string|max:255',
        ]);
        $customer = Customer::query()->findOrFail($id);
        if ($customer) {
            $imageFile = $request->file("image");
            if ($imageFile) {
                $extension = $imageFile->getClientOriginalExtension();
                $url = strtolower(Str::random(10)) . time() . "." . $extension;
                $data['image'] = $url;
                unset($data['_method']);
            } else {
                unset($data['image']);
                unset($data['_method']);
            }
            $customerUpdate = Customer::query()->where("id", $customer->id)->update($data);
            if ($customerUpdate) {
                return response()->json("Cập nhật thành công");
            } else {
                return response()->json("Cập nhật thất bại");
            }
        }

    }

    public function destroy($id)
    {
        $customer = Customer::query()->findOrFail($id);
        if ($customer) {
            Customer::query()->where("id", $id)->delete();
            return response()->json("Xoá thành công");
        }
        return response()->json("Xoá thất bại");
    }
}
