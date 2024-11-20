<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class CartController extends Controller
{
    public function show(Request $request)
    {
        $size = $request->query('size');
        $userId = JWTAuth::parseToken()->authenticate()->id;

        $carts = Cart::where('user_id', $userId)
            ->with([
                'productAtt:id,product_id,size_id,color_id,stock_quantity',
                'productAtt.size:id,name',
                'productAtt.color:id,name',
                'productAtt.colorImage:id,product_id,color_id,image'
            ])
            ->orderByDesc('id')
            ->get();

        $result = $carts->map(function ($cart) {
            return [
                'id' => $cart->id,
                'name' => $cart->productAtt->product->name,
                'regular_price' => $cart->productAtt->product->regular_price ?? null,
                'reduced_price' => $cart->productAtt->product->reduced_price ?? null,
                'product_att_id' => $cart->product_att_id,
                'quantity' => $cart->quantity,
                'product_att' => [
                    'color_id' => $cart->productAtt->color_id ?? null,
                    'image' => $cart->productAtt->colorImage->image ?? null,
                    'color_name' => $cart->productAtt->color->name ?? null,
                    'size_id' => $cart->productAtt->size_id ?? null,
                    'size_name' => $cart->productAtt->size->name ?? null,
                    'stock_quantity' => $cart->productAtt->stock_quantity,
                ],
            ];
        });

        if ($size) {
            $result = $result->slice(0, $size)->values();
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_att_id' => 'required|integer|exists:product_atts,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $userId = JWTAuth::parseToken()->authenticate()->id;

        $cart = Cart::query()->where('user_id', $userId)
            ->where('product_att_id', $request->product_att_id)
            ->first();

        if ($cart) {
            Cart::query()->where('id', $cart->id)->update([
                'quantity' => $cart->quantity + $request->quantity
            ]);
        } else {
            Cart::query()->create([
                'product_att_id' => $request->product_att_id,
                'user_id' => $userId,
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
