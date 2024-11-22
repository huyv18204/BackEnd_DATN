<?php

namespace App\Traits;

trait applyFilters
{
    protected function Filters($query, $request)
    {
        $query->when($request->query('id'), fn($q, $id) => $q->where('id', $id));
        $query->when($request->query('categoryId'), fn($q, $categoryId) => $q->where('category_id', $categoryId));
        $query->when($request->query('name'), fn($q, $name) => $q->where('name', 'like', '%' . $name . '%'));
        $query->when($request->query('sizeId'), fn($q, $sizeId) => $q->where('size_id', $sizeId));
        $query->when($request->query('colorId'), fn($q, $sizeId) => $q->where('color_id', $sizeId));
        $query->when($request->query('minPrice'), fn($q, $minPrice) => $q->where('regular_price', '>=', $minPrice));
        $query->when($request->query('maxPrice'), fn($q, $maxPrice) => $q->where('regular_price', '<=', $maxPrice));
        $query->orderBy('created_at', $request->query('sort', 'ASC'));
        $size = $request->query('size');
        return $size ? $query->paginate($size) : $query->get();
    }
}
