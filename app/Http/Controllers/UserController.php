<?php

namespace App\Http\Controllers;

use App\Http\Response\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'ASC');

        $size = $request->query('size');

        $query = User::query()->whereNotIn('role', ['shipper', 'admin']);

        if ($name = $request->query('name')) {
            $query->where('name', 'LIKE', '%' . $name . '%');
        }

        if ($email = $request->query('email')) {
            $query->where('email', 'LIKE', '%' . $email . '%');
        }

        if ($id = $request->query('id')) {
            $query->where('id', $id);
        }
        $query->orderBy('id', $sort);
        $users = $size ? $query->paginate($size) : $query->get();

        return ApiResponse::data($users);
    }

    public function toggleStatus($id)
    {
        $user = User::find($id);

        if (!$user) {
            return ApiResponse::message('Người dùng không tồn tại', 404);
        }

        if ($user->role == 'admin') {
            return ApiResponse::message('Bạn không có quyền khóa tài khoản người dùng này');
        }

        $user->is_blocked = !$user->is_blocked;
        $user->save();

        $action = $user->is_blocked ? 'Khóa' : 'Khôi phục';
        return ApiResponse::message("{$action} tài khoản có email là {$user->email} thành công");
    }

    public function blackList(Request $request)
    {
        $sort = $request->query('sort', 'ASC');
        $size = $request->query('size');

        $query = User::query();

        if ($name = $request->query('name')) {
            $query->where('name', 'LIKE', '%' . $name . '%');
        }

        if ($email = $request->query('email')) {
            $query->where('email', 'LIKE', '%' . $email . '%');
        }

        if ($id = $request->query('id')) {
            $query->where('id', $id);
        }

        $query->where('is_blocked', 1)->orderBy('id', $sort);

        $users = $size ? $query->paginate($size) : $query->get();

        return ApiResponse::data($users);
    }
}
