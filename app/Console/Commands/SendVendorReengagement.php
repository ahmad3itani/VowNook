<?php

namespace App\Console\Commands;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\VendorProfile;
use App\Notifications\VendorUnansweredInquiries;
use Illuminate\Console\Command;

class SendVendorReengagement extends Command
{
    protected $signature = 'vendors:unanswered-inquiries';

    protected $description = 'Nudge vendors who have inquiries older than 24h with no reply';

    public function handle(): int
    {
        // Count unanswered, still-open inquiries per vendor profile.
        $counts = Inquiry::query()
            ->where('status', InquiryStatus::Requested->value)
            ->whereNull('first_response_at')
            ->where('created_at', '<=', now()->subDay())
            ->selectRaw('vendor_profile_id, COUNT(*) as cnt')
            ->groupBy('vendor_profile_id')
            ->pluck('cnt', 'vendor_profile_id');

        $sent = 0;

        VendorProfile::query()
            ->whereIn('id', $counts->keys())
            ->with('user')
            ->each(function (VendorProfile $profile) use ($counts, &$sent) {
                if ($profile->user === null) {
                    return;
                }

                $profile->user->notify(new VendorUnansweredInquiries((int) $counts[$profile->id]));
                $sent++;
            });

        $this->info("Sent {$sent} vendor re-engagement notification(s).");

        return self::SUCCESS;
    }
}
