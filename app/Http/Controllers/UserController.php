<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'ASC');

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

        $query->where('is_blocked', 0)->orderBy('id', $sort);
        $users = $size ? $query->paginate($size) : $query->get();

        return response()->json($users);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $users = User::query()->create($data);
        $message = $users ? 'Thêm mới người dùng thành công' : 'Thêm mới người dùng thất bại';
        return response()->json(['message' => $message], $users ? 201 : 400);
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:admin,user',
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Người dùng không tồn tại'], 404);
        }

        if ($user->is_blocked == true) {
            return response()->json(['message' => 'Bạn không thể cập nhật quyền cho tài khoản đang bị khóa']);
        }

        if ($user->id === auth('api')->id()) {
            return response()->json(["message" => "Bạn không thể thay đổi quyền của chính mình"], 400);
        }

        $user->role = $request->role;
        $user->save();

        return response()->json(['message' => 'Cập nhật vai trò thành công']);
    }


    public function toggleBlackList(string $id)
    {
        $user = User::find($id);
    
        if (!$user) {
            return response()->json(['message' => 'Người dùng không tồn tại'], 404);
        }
    
        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Tài khoản này chưa được xác thực'], 403);
        }
    
        if ($user->id === auth()->user()->id) {
            return response()->json(['message' => 'Bạn không thể tự thêm chính mình vào danh sách đen'], 403);
        }
    
        if ($user->role == 'admin') {
            return response()->json(['message' => 'Bạn không có quyền khóa tài khoản người dùng này'], 403);
        }
    
        $user->is_blocked = !$user->is_blocked;
        $user->save();
    
        $action = $user->is_blocked ? 'Đã thêm vào danh sách đen' : 'Khôi phục';
        return response()->json(['message' => "{$action} người dùng có email là {$user->email} thành công"], 200);
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

        return response()->json($users);
    }
}
