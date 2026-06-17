<?php

namespace App\Http\Controllers;

use App\Models\Wedding;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebsiteMediaController extends Controller
{
    public function serve(Wedding $wedding, string $type, string $filename): StreamedResponse
    {
        abort_unless(in_array($type, ['hero', 'story', 'gallery', 'music', 'registry', 'travel'], true), 404);

        // basename() guards against ../ traversal in the filename segment.
        $path = "websites/{$wedding->id}/{$type}/" . basename($filename);

        abort_unless(Storage::exists($path), 404);

        return Storage::response($path);
    }
}
