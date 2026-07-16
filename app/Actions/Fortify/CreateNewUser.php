<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\AccountType;
use App\Enums\VendorProfileStatus;
use App\Models\User;
use App\Models\VendorProfile;
use App\Support\Conversions;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user. Vendors get a draft
     * marketplace profile so their dashboard has something to resolve.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'account_type' => ['sometimes', Rule::in(AccountType::values())],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'You must accept the Terms of Service and Privacy Policy to create an account.',
        ])->validate();

        $accountType = AccountType::tryFrom($input['account_type'] ?? '') ?? AccountType::Couple;

        // Referral attribution: ?ref=CODE carried through registration.
        $referrerId = null;
        if (! empty($input['ref'])) {
            $referrerId = User::where('referral_code', strtoupper(trim($input['ref'])))->value('id');
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'account_type' => $accountType->value,
            'referred_by' => $referrerId,
            // CASL: signing up is express consent to lifecycle email; record it.
            'marketing_consent_at' => now(),
            // Record acceptance of the Terms of Service + Privacy Policy (validated
            // as `accepted` above) for a consumer-protection audit trail.
            'terms_accepted_at' => now(),
        ]);

        if ($accountType === AccountType::Vendor) {
            VendorProfile::create([
                'user_id' => $user->id,
                'business_name' => $input['name'],
                'category' => 'other',
                'email' => $user->email,
                'status' => VendorProfileStatus::Draft->value,
            ]);
        }

        // Planners manage many client weddings — their tier lifts the
        // one-wedding cap. Billing enforcement arrives with Stripe.
        if ($accountType === AccountType::Planner) {
            $user->forceFill(['plan' => 'planner'])->save();
        }

        Conversions::flash('sign_up', 'CompleteRegistration', ['method' => 'email']);

        $user->notify(new \App\Notifications\WelcomeNotification());

        // Let admins know a new account joined (email + in-app bell).
        \Illuminate\Support\Facades\Notification::send(
            User::where('is_admin', true)->get(),
            new \App\Notifications\NewUserRegistered($user),
        );

        return $user;
    }
}
