<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request,$id)
    {
        $size = $request->query('size');
        $carts = Cart::query()->where('user_id', $id)->orderByDesc('id');
        $carts = $size ? $carts->paginate() : $carts->get();
        return response()->json($carts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'product_att_id' => 'required|integer|exists:product_atts,id',
            'user_id' => 'required|integer|exists:users,id',
            'quantity' => 'required|integer|min:1'
        ]);


        $cart = Cart::query()->where('user_id', $request->user_id)
            ->where('product_att_id', $request->product_att_id)
            ->where('product_id', $request->product_id)->first();

        if ($cart) {
            Cart::query()->where('id', $cart->id)->update([
                'quantity' => $cart->quantity + $request->quantity
            ]);
        } else {
            Cart::query()->create([
                'product_id' => $request->product_id,
                'product_att_id' => $request->product_att_id,
                'user_id' => $request->user_id,
                'quantity' => $request->quantity
            ]);
        }
        return response()->json([
            "message" => "Thêm sản phẩm vào giỏ hàng thành công"
        ], 201);
    }


    public function destroy($id)
    {
        $cart = Cart::query()->find($id);
        if (!$cart) {
            return response()->json([
                "error" => "Sản phẩm không tồn tại trong giỏ hàng"
            ]);
        }
        $response = $cart->delete();

        if (!$response) {
            return response()->json([
                "error" => "Xoá sản phẩm thất bại"
            ]);
        }
        return response()->json([
            "message" => "Xoá sản phẩm thành công"
        ]);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);


        $cart = Cart::query()->find($id);
        if (!$cart) {
            return response()->json([
                "error" => "Sản phẩm không tồn tại trong giỏ hàng"
            ]);
        }
        $response = $cart->update([
            "quantity" => $request->quantity
        ]);

        if (!$response) {
            return response()->json([
                "error" => "Cập nhật số lượng thất bại"
            ]);
        }

        return response()->json([
            "message" => "Cập nhật thành công"
        ]);
    }
}
