<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\AccountType;
use App\Enums\VendorProfileStatus;
use App\Models\User;
use App\Models\VendorProfile;
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

        $user->notify(new \App\Notifications\WelcomeNotification());

        return $user;
    }
}
