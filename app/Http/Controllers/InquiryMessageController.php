<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\InquiryMessage;
use App\Support\CurrentVendorProfile;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InquiryMessageController extends Controller
{
    public function __construct(
        protected CurrentWedding $wedding,
        protected CurrentVendorProfile $vendorProfile,
    ) {}

    public function store(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeParticipant($inquiry);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        InquiryMessage::create([
            'inquiry_id'    => $inquiry->id,
            'sender_user_id' => Auth::id(),
            'body'          => $data['body'],
        ]);

        // A vendor-side message counts as the vendor's first response.
        if ($this->vendorProfile->id() === $inquiry->vendor_profile_id) {
            $inquiry->recordVendorResponse();
        }

        return back()->with('status', 'message-sent');
    }

    private function authorizeParticipant(Inquiry $inquiry): void
    {
        $user = Auth::user();

        // Couple side — must own the wedding.
        if ($this->wedding->id() === $inquiry->wedding_id) {
            return;
        }

        // Vendor side — must own the vendor profile on the inquiry.
        if ($this->vendorProfile->id() === $inquiry->vendor_profile_id) {
            return;
        }

        abort(403);
    }
}
