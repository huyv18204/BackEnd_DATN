<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('upload_image')) {
    function upload_image($image, $folder = 'uploads')
    {
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        return $image->storeAs($folder, $filename, 'public');
    }
}

if (!function_exists('delete_image')) {
    function delete_image($path)
    {
        return Storage::disk('public')->delete($path);
    }
}