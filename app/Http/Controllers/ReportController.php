<?php

namespace App\Http\Controllers;

use App\Enums\ReportReason;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\ReportFiled;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/** A logged-in user flags a vendor listing or a review for admin review. */
class ReportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['vendor', 'review'])],
            'id' => ['required'],
            'reason' => ['required', Rule::in(ReportReason::values())],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $reportable = $data['type'] === 'vendor'
            ? VendorProfile::where('slug', $data['id'])->firstOrFail()
            : Review::findOrFail($data['id']);

        $report = $reportable->morphMany(Report::class, 'reportable')->create([
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
            'reporter_user_id' => $request->user()->id,
        ]);

        Notification::send(User::where('is_admin', true)->get(), new ReportFiled($report));

        return back()->with('status', 'report-submitted');
    }
}
