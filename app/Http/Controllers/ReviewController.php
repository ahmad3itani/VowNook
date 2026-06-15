<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Couple side — leave a review on a booking made through the marketplace. */
class ReviewController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function store(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();
        abort_if($wedding === null, 403, 'No active wedding.');

        $data = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $booking = Booking::findOrFail($data['booking_id']);

        abort_unless($booking->wedding_id === $wedding->id, 403);

        if (Review::where('booking_id', $booking->id)->exists()) {
            throw ValidationException::withMessages([
                'booking_id' => 'You have already reviewed this booking.',
            ]);
        }

        // Derive everything tenant-sensitive from the booking, never the request.
        Review::create([
            'booking_id' => $booking->id,
            'wedding_id' => $booking->wedding_id,
            'vendor_profile_id' => $booking->vendor_profile_id,
            'couple_user_id' => Auth::id(),
            'rating' => $data['rating'],
            'body' => $data['body'] ?? null,
        ]);

        Review::syncVendorRating($booking->vendor_profile_id);

        return back()->with('status', 'review-submitted');
    }
}
