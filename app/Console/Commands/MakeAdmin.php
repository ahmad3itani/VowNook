<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/** Grant (or revoke) platform admin access to a user by email. */
class MakeAdmin extends Command
{
    protected $signature = 'user:make-admin {email} {--revoke}';

    protected $description = 'Grant admin access to a user by email (or --revoke to remove it)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        $attrs = ['is_admin' => ! $this->option('revoke')];

        // Bootstrap convenience: mark the email verified so the admin can reach
        // admin pages before outbound email is configured.
        if (! $this->option('revoke') && $user->email_verified_at === null) {
            $attrs['email_verified_at'] = now();
        }

        $user->forceFill($attrs)->save();

        $this->info($user->email.($this->option('revoke') ? ' is no longer an admin.' : ' is now an admin (and email verified).'));

        return self::SUCCESS;
    }
}
