<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets a platform admin enter any couple's wedding workspace for support, with
 * full access. The target wedding is held in the session (not the admin's own
 * current_wedding_id, which stays untouched) and resolved by SetCurrentWedding.
 */
class AdminSupportController extends Controller
{
    /** Begin a support session for the given wedding. */
    public function enter(Request $request, Wedding $wedding): RedirectResponse
    {
        $request->session()->put('support_wedding_id', $wedding->id);

        return redirect()->route('dashboard');
    }

    /** End the support session and return to the admin console. */
    public function exit(Request $request): RedirectResponse
    {
        $request->session()->forget('support_wedding_id');

        return redirect()->route('admin.dashboard');
    }
}
