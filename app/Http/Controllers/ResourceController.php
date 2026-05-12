<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Services\TextExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $resources = Resource::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json($resources->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'original_filename' => $r->original_filename,
                'size_bytes' => $r->size_bytes,
            ]));
        }

        return view('resources.index', compact('resources'));
    }

    public function listJson()
    {
        $resources = Resource::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($resources->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'original_filename' => $r->original_filename,
            'size_bytes' => $r->size_bytes,
        ]));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $size = $file->getSize();

        $path = $file->store('resources', 'public');
        $fullPath = storage_path('app/public/' . $path);

        $extractedText = '';
        try {
            $extractor = new TextExtractorService();
            $extractedText = $extractor->extractText($fullPath);
        } catch (\Exception $e) {
            Log::warning('Resource text extraction failed: ' . $e->getMessage());
        }

        $resource = Resource::create([
            'user_id' => Auth::id(),
            'name' => pathinfo($originalName, PATHINFO_FILENAME),
            'original_filename' => $originalName,
            'path' => $path,
            'extracted_text' => $extractedText,
            'size_bytes' => $size,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'id' => $resource->id,
                'name' => $resource->name,
                'original_filename' => $resource->original_filename,
                'size_bytes' => $resource->size_bytes,
            ]);
        }

        return redirect()->route('resources.index')->with('success', 'Resource uploaded.');
    }

    public function show(Resource $resource)
    {
        if ($resource->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'id' => $resource->id,
            'name' => $resource->name,
            'original_filename' => $resource->original_filename,
            'size_bytes' => $resource->size_bytes,
            'created_at' => $resource->created_at->toDateTimeString(),
        ]);
    }

    public function destroy(Resource $resource)
    {
        if ($resource->user_id !== Auth::id()) {
            abort(403);
        }

        if ($resource->path && Storage::disk('public')->exists($resource->path)) {
            Storage::disk('public')->delete($resource->path);
        }

        $resource->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('resources.index')->with('success', 'Resource deleted.');
    }
}
