<?php

namespace App\Http\Controllers;

use App\Models\ProductAtt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductAttController extends Controller
{
    public function index(Request $request, $product_id)
    {
        $size = $request->query('size');
        $sizeId = $request->query('sizeId');
        $colorId = $request->query('colorId');


        $query = DB::table('product_att_size')
            ->join('product_atts', 'product_att_size.product_att_id', '=', 'product_atts.id')
            ->join('sizes', 'product_att_size.size_id', '=', 'sizes.id')
            ->join('colors', 'product_atts.color_id', '=', 'colors.id')
            ->where('product_atts.product_id', $product_id)
            ->select(

                'sizes.name as size_name',
                'colors.name as color_name', 'product_atts.*', 'product_att_size.stock_quantity');

        if ($colorId) {
            $query->where('product_atts.color_id', $colorId);
        }
        if ($sizeId) {
            $query->where('product_att_size.size_id', $colorId);
        }

        if ($size) {
            $productVariants = $query->paginate($size);
        } else {
            $productVariants = $query->get();
        }

        return response()->json($productVariants);

    }

    public function store(Request $request, int $product_id)
    {
        DB::beginTransaction();

        try {
            $variants = $request->validate([
                '*' => 'required|array',
                '*.size_id' => 'required|exists:sizes,id',
                '*.color_id' => 'required|exists:colors,id',
                '*.stock_quantity' => 'required|integer|min:0',
                '*.image' => 'nullable|string|max:255',
            ]);

            $errors = [];

            foreach ($variants as $variant) {
                $variant['product_id'] = $product_id;
                $existingVariantSize = ProductAtt::query()
                    ->where('color_id', $variant['color_id'])
                    ->where('product_id', $product_id)
                    ->whereHas('sizes', function ($query) use ($variant) {
                        $query->where('sizes.id', $variant['size_id']);
                    })
                    ->first();


                $existingVariant = ProductAtt::query()
                    ->where('color_id', $variant['color_id'])
                    ->where('product_id', $product_id)
                    ->first();

                if ($existingVariantSize) {
                    $errors[] = [
                        'message' => 'Size và color đã tồn tại'
                    ];
                } else {
                    if (empty($existingVariant)) {
                        $responseProductAtt = ProductAtt::query()->create([
                            "product_id" => $variant['product_id'],
                            "color_id" => $variant['color_id'],
                            "image" => $variant['image']
                        ]);

                        $responseProductAttSize = DB::table('product_att_size')->insert([
                            "product_att_id" => $responseProductAtt->id,
                            "size_id" => $variant['size_id'],
                            "stock_quantity" => $variant['stock_quantity'],
                        ]);

                        if (!$responseProductAtt || !$responseProductAttSize) {
                            DB::rollBack();
                            return response()->json([
                                'message' => 'Thêm thất bại',
                            ], 500);
                        }
                    } else {
                        $responseProductAttSize = DB::table('product_att_size')->insert([
                            "product_att_id" => $existingVariant->id,
                            "size_id" => $variant['size_id'],
                            "stock_quantity" => $variant['stock_quantity'],
                        ]);
                        if (!$responseProductAttSize) {
                            DB::rollBack();
                            return response()->json([
                                'message' => 'Thêm thất bại',
                            ], 500);
                        }
                    }
                }
            }

            if (count($errors) > 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Một số biến thể không được thêm thành công',
                    'errors' => $errors
                ], 422);
            }

            DB::commit();
            return response()->json([
                'message' => 'Thêm mới thành công',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, int $product_id, int $id, int $size_id)
    {
        DB::beginTransaction();
        try {
            $productAtt = ProductAtt::query()->with('sizes')->where([
                ['id', $id],
                ['product_id', $product_id]
            ])->first();

            if (empty($productAtt) || empty($productAtt->sizes[0]->id)) {
                DB::rollBack();
                return response()->json(['error' => "Biến thể không tồn tại"]);
            }

            $data = $request->validate([
                'stock_quantity' => 'required|integer|min:0',
                'image' => 'nullable|string|max:255',
            ]);

            $productAtt->update([
                "image" => $data['image']
            ]);
            DB::table('product_att_size')->where([
                ['product_att_id', $id],
                ['size_id', $size_id],
            ])->update([
                "stock_quantity" => $data['stock_quantity']
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Cập nhật thành công',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $product_id, int $id, int $size_id)
    {
        DB::beginTransaction();
        try {
            $productAtt = ProductAtt::query()->with('sizes')->where([
                ['id', $id],
                ['product_id', $product_id]
            ])->first();

            if (empty($productAtt) || empty($productAtt->sizes[0]->id)) {
                DB::rollBack();
                return response()->json(['error' => "Biến thể không tồn tại"]);
            }
            $countRecord = DB::table('product_att_size')->where('product_att_id', $id)->count();
            if ($countRecord == 1) {
                $productAtt->delete();
                DB::table('product_att_size')->where([
                    ['product_att_id', $id],
                    ['size_id', $size_id]
                ])->delete();
            } else {
                DB::table('product_att_size')->where([
                    ['product_att_id', $id],
                    ['size_id', $size_id]
                ])->delete();
            }
            DB::commit();
            return response()->json(['message' => 'Xoá thành công']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }


    public function show(int $product_id, int $id, int $size_id)
    {
        $productAtt = ProductAtt::query()->with([
            'sizes' => function ($query) use ($size_id) {
                $query->select('id', 'name')->where("id", $size_id)->withTrashed();
            },
            'color' => function ($query) {
                $query->select('id', 'name')->withTrashed();
            }
        ])->where([
            ['id', $id],
            ['product_id', $product_id]
        ])->first();

        if (empty($productAtt) || empty($productAtt->sizes[0]->id)) {
            return response()->json(['error' => "Biến thể không tồn tại"]);
        }

        return response()->json($productAtt);
    }
}

