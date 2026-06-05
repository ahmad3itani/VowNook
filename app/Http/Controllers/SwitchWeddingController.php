<?php

namespace App\Http\Controllers;

use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchWeddingController extends Controller
{
    /**
     * Set the authenticated user's active wedding.
     */
    public function __invoke(Request $request, Wedding $wedding): RedirectResponse
    {
        $user = $request->user();

        // Authorize: the user must be able to view this wedding.
        abort_unless($wedding->roleFor($user) !== null || $user->is_admin, 403);

        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return back();
    }
}
