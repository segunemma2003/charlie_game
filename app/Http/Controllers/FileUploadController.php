<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image; // Laravel 11 compatible

class FileUploadController extends Controller
{
    public function uploadCardImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $image = $request->file('image');
        $filename = 'cards/' . uniqid() . '.' . $image->getClientOriginalExtension();

        // Using Intervention Image v3 (Laravel 11 compatible)
        $processedImage = Image::read($image)
            ->resize(400, 400)
            ->encode();

        Storage::put($filename, $processedImage);

        return response()->json([
            'success' => true,
            'path' => $filename,
            'url' => Storage::url($filename)
        ]);
    }

    public function uploadTournamentBanner(Request $request)
    {
        $request->validate([
            'banner' => 'required|image|mimes:jpeg,png,jpg|max:5120'
        ]);

        $banner = $request->file('banner');
        $filename = 'tournaments/' . uniqid() . '.' . $banner->getClientOriginalExtension();

        $processedBanner = Image::read($banner)
            ->resize(1200, 400)
            ->encode();

        Storage::put($filename, $processedBanner);

        return response()->json([
            'success' => true,
            'path' => $filename,
            'url' => Storage::url($filename)
        ]);
    }

    public function uploadBattleAnimation(Request $request)
    {
        $request->validate([
            'animation' => 'required|file|mimes:gif,mp4,webm|max:10240'
        ]);

        $animation = $request->file('animation');
        $filename = 'animations/' . uniqid() . '.' . $animation->getClientOriginalExtension();

        Storage::put($filename, file_get_contents($animation));

        return response()->json([
            'success' => true,
            'path' => $filename,
            'url' => Storage::url($filename)
        ]);
    }

    public function deleteFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        if (Storage::exists($request->path)) {
            Storage::delete($request->path);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'File not found'
        ], 404);
    }
}
