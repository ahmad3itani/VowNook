<?php

namespace App\Http\Controllers;

use App\Support\CurrentWedding;
use App\Support\GuestReminders;
use Illuminate\Http\RedirectResponse;

/** Couple-triggered "remind everyone who hasn't replied yet" action. */
class GuestReminderController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function send(): RedirectResponse
    {
        $count = GuestReminders::sendFor($this->current->get());

        return back()->with('status', "rsvp-reminders-sent:{$count}");
    }
}
