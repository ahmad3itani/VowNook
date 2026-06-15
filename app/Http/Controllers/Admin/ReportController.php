<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Review;
use App\Models\VendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        $reports = Report::with('reporter:id,name', 'reportable')
            ->latest()
            ->get()
            ->map(fn (Report $r) => [
                'id' => $r->id,
                'type' => class_basename($r->reportable_type),
                'reason' => $r->reason->label(),
                'details' => $r->details,
                'status' => $r->status,
                'reporter' => $r->reporter?->name,
                'created_at' => $r->created_at?->toDateString(),
                'target' => $this->targetLabel($r),
                'target_url' => $this->targetUrl($r),
            ]);

        return Inertia::render('admin/reports', [
            'reports' => $reports,
            'open_count' => $reports->where('status', 'open')->count(),
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'reviewed', 'actioned', 'dismissed'])],
        ]);

        $report->update($data);

        return back()->with('status', 'report-updated');
    }

    private function targetLabel(Report $r): string
    {
        $t = $r->reportable;

        return match (true) {
            $t instanceof VendorProfile => $t->business_name,
            $t instanceof Review => 'Review #'.$t->id,
            default => 'Removed',
        };
    }

    private function targetUrl(Report $r): ?string
    {
        $t = $r->reportable;

        if ($t instanceof VendorProfile) {
            return route('public.vendor.show', $t->slug);
        }
        if ($t instanceof Review && $t->vendorProfile) {
            return route('public.vendor.show', $t->vendorProfile->slug);
        }

        return null;
    }
}
