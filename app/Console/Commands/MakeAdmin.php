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

        $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

        $this->info($user->email.($this->option('revoke') ? ' is no longer an admin.' : ' is now an admin.'));

        return self::SUCCESS;
    }
}
