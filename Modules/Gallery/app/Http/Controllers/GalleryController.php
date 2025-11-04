<?php

namespace Modules\Gallery\App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Gallery\Models\File;

class GalleryController extends Controller
{

    public function index()
    {
        return view("gallery::backend.layouts.gallery.index");
    }

    public function list()
    {
        $files = File::where('type', 'image')->orderBy('id', 'desc')->paginate(12);
        return response()->json([
            'status' => 'success',
            'files' => $files
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        $files = $request->file('images');

        foreach ($files as $file) {
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path("uploads/gallery/"), $filename);
            File::create([
                'name' => $filename,
                'path' => "uploads/gallery/$filename",
                'type' => 'image',
                'mime_type' => $file->getClientMimeType()
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Images uploaded successfully'
        ]);
    }

    public function destroy($id)
    {
        $id = $id;

        $file = File::findOrFail($id);

        if (file_exists(public_path($file->path))) {
            unlink(public_path($file->path));
        }

        $file->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Image deleted successfully'
        ]);
    }
}
