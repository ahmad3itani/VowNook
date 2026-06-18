<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin oversight of every account on the platform: a searchable directory and a
 * per-user "360" detail page with full context (their data, last login, recent
 * activity) and support actions (impersonate, change/comp plan, suspend, send a
 * password reset, resend verification).
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->withCount('ownedWeddings')
            ->when($search !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->latest()
            ->limit(100)
            ->get(['id', 'name', 'email', 'account_type', 'plan', 'is_admin', 'suspended_at', 'last_login_at', 'created_at'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'account_type' => $u->account_type->value,
                'plan' => $u->plan,
                'is_admin' => (bool) $u->is_admin,
                'suspended' => $u->isSuspended(),
                'weddings_count' => $u->owned_weddings_count,
                'last_login_at' => $u->last_login_at?->toIso8601String(),
                'created_at' => $u->created_at?->toDateString(),
            ]);

        return Inertia::render('admin/users', [
            'users' => $users,
            'search' => $search,
            'plans' => $this->planOptions(),
        ]);
    }

    public function show(User $user): Response
    {
        $weddingIds = $user->ownedWeddings()->pluck('id');

        // Bookings + spend/earnings depend on whether they're a couple or vendor.
        $vendorProfile = $user->vendorProfile()->first();

        $bookingsQuery = $vendorProfile
            ? Booking::where('vendor_profile_id', $vendorProfile->id)
            : Booking::whereIn('wedding_id', $weddingIds);

        $activity = ActivityLog::query()
            ->with('actor:id,name')
            ->where(fn ($q) => $q->where('actor_id', $user->id)
                ->orWhere(fn ($w) => $w->where('subject_type', $user->getMorphClass())
                    ->where('subject_id', $user->id)))
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (ActivityLog $a) => [
                'id' => $a->id,
                'action' => $a->action,
                'actor' => $a->actor?->name,
                'description' => $a->description,
                'ip' => $a->ip_address,
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/user-show', [
            'subject' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => $user->account_type->value,
                'plan' => $user->plan,
                'plan_comped_until' => $user->plan_comped_until?->toIso8601String(),
                'is_admin' => (bool) $user->is_admin,
                'suspended' => $user->isSuspended(),
                'suspended_reason' => $user->suspended_reason,
                'email_verified' => $user->hasVerifiedEmail(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'last_login_ip' => $user->last_login_ip,
                'created_at' => $user->created_at?->toIso8601String(),
                'referral_code' => $user->referral_code,
                'referrals_count' => $user->referrals()->count(),
            ],
            'weddings' => $user->ownedWeddings()
                ->withCount('guests')
                ->latest()
                ->get(['id', 'name', 'slug', 'event_date'])
                ->map(fn ($w) => [
                    'id' => $w->id,
                    'name' => $w->name,
                    'slug' => $w->slug,
                    'event_date' => $w->event_date?->toDateString(),
                    'guests_count' => $w->guests_count,
                ]),
            'vendor_profile' => $vendorProfile ? [
                'id' => $vendorProfile->id,
                'business_name' => $vendorProfile->business_name,
                'slug' => $vendorProfile->slug,
                'status' => $vendorProfile->status->value,
                'rating_avg' => $vendorProfile->rating_avg,
                'rating_count' => $vendorProfile->rating_count,
            ] : null,
            'stats' => [
                'bookings' => (clone $bookingsQuery)->count(),
                'gmv' => (int) ((clone $bookingsQuery)
                    ->where('status', '!=', BookingStatus::Cancelled->value)
                    ->sum('total_cents') / 100),
                'open_inquiries' => $vendorProfile
                    ? Inquiry::where('vendor_profile_id', $vendorProfile->id)->whereIn('status', ['requested', 'offered'])->count()
                    : Inquiry::whereIn('wedding_id', $weddingIds)->whereIn('status', ['requested', 'offered'])->count(),
                'support_tickets' => $user->supportTickets()->count(),
            ],
            'activity' => $activity,
            'plans' => $this->planOptions(),
            'can_impersonate' => ! $user->is_admin,
        ]);
    }

    public function updatePlan(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('plans.tiers')))],
        ]);

        $user->forceFill(['plan' => $data['plan'], 'plan_comped_until' => null])->save();

        ActivityLogger::log('admin.user.plan', $user, ['plan' => $data['plan']]);

        return back()->with('status', 'user-plan-updated');
    }

    /** Comp a paid plan for a fixed number of days (support gesture). */
    public function comp(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('plans.tiers')))],
            'days' => ['required', 'integer', 'min:1', 'max:1825'],
        ]);

        $user->forceFill([
            'plan' => $data['plan'],
            'plan_comped_until' => now()->addDays($data['days']),
        ])->save();

        ActivityLogger::log('admin.user.comp', $user, $data);

        return back()->with('status', 'user-comped');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403, 'Admins cannot be suspended.');

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user->forceFill([
            'suspended_at' => now(),
            'suspended_reason' => $data['reason'] ?? null,
        ])->save();

        ActivityLogger::log('admin.user.suspend', $user, ['reason' => $data['reason'] ?? null]);

        return back()->with('status', 'user-suspended');
    }

    public function unsuspend(User $user): RedirectResponse
    {
        $user->forceFill(['suspended_at' => null, 'suspended_reason' => null])->save();

        ActivityLogger::log('admin.user.unsuspend', $user);

        return back()->with('status', 'user-unsuspended');
    }

    public function sendPasswordReset(User $user): RedirectResponse
    {
        Password::sendResetLink(['email' => $user->email]);

        ActivityLogger::log('admin.user.password_reset', $user);

        return back()->with('status', 'password-reset-sent');
    }

    public function resendVerification(User $user): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            ActivityLogger::log('admin.user.resend_verification', $user);
        }

        return back()->with('status', 'verification-resent');
    }

    /** @return list<array{value:string,label:string}> */
    private function planOptions(): array
    {
        return array_map(
            fn (string $key, array $tier) => ['value' => $key, 'label' => $tier['name']],
            array_keys(config('plans.tiers')),
            array_values(config('plans.tiers')),
        );
    }
}
