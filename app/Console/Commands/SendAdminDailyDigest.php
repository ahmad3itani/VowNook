<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Report;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use App\Notifications\AdminDailyDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendAdminDailyDigest extends Command
{
    protected $signature = 'admin:daily-digest';

    protected $description = 'Email admins a daily summary of platform activity.';

    public function handle(): int
    {
        $since = now()->subDay();

        $stats = [
            'new_users' => User::where('created_at', '>=', $since)->count(),
            'new_weddings' => Wedding::where('created_at', '>=', $since)->count(),
            'vendors_pending' => VendorProfile::where('status', 'pending_review')->count(),
            'new_bookings' => Booking::where('created_at', '>=', $since)->count(),
            'gmv_cents' => (int) Booking::where('created_at', '>=', $since)->sum('total_cents'),
            'open_reports' => Report::where('status', 'open')->count(),
        ];

        // Quiet day → don't send an empty email.
        $noteworthy = $stats['new_users'] + $stats['new_weddings'] + $stats['new_bookings']
            + $stats['vendors_pending'] + $stats['open_reports'];

        if ($noteworthy === 0) {
            $this->info('Nothing to report today — digest skipped.');

            return self::SUCCESS;
        }

        $admins = User::where('is_admin', true)->get();
        Notification::send($admins, new AdminDailyDigest($stats));

        $this->info('Admin digest sent to '.$admins->count().' admin(s).');

        return self::SUCCESS;
    }
}
