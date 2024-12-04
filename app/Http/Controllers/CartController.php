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
                'productAtt' => function ($query) {
                    $query->select('id', 'product_id', 'color_id', 'size_id', 'sku', 'regular_price', 'reduced_price', 'image', 'stock_quantity', 'is_active')
                        ->with('product:id,regular_price,reduced_price');
                },
                'productAtt.size:id,name',
                'productAtt.color:id,name',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function ($cart) {
                $productAtt = $cart->productAtt;
    
                if (!$productAtt->regular_price) {
                    $productAtt->regular_price = $productAtt->product->regular_price;
                }
    
                if (!$productAtt->reduced_price) {
                    $productAtt->reduced_price = $productAtt->product->reduced_price;
                }
    
                unset($productAtt->product); 
    
                return $cart;
            });
    
        if ($size) {
            $carts = $carts->slice(0, $size)->values();
        }
    
        return response()->json($carts);
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
