<?php

namespace App\Http\Controllers;

use App\Models\GuestSend;
use Illuminate\Http\Response;

/**
 * Email open-tracking pixel. A guest's mail client loading /e/{token}.gif flips
 * the matching send's opened_at (once) and returns a 1x1 transparent GIF.
 */
class EmailTrackController extends Controller
{
    /** Base64 of a 1x1 transparent GIF. */
    private const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function pixel(string $token): Response
    {
        $send = GuestSend::where('token', $token)->first();

        if ($send && $send->opened_at === null) {
            $send->forceFill(['opened_at' => now()])->save();
        }

        return response(base64_decode(self::PIXEL), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
        ]);
    }
}
