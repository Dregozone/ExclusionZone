<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('reset:password {email : The email address of the account} {newPlainPassword? : The new password (prompted for if omitted)}')]
#[Description('Set a new password for the user with the given email address')]
class ResetPassword extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("No user found with email [{$this->argument('email')}].");

            return self::FAILURE;
        }

        $newPlainPassword = $this->argument('newPlainPassword') ?? $this->secret('New password');

        $validator = Validator::make(
            ['password' => $newPlainPassword],
            ['password' => ['required', 'string', Password::default()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user->update(['password' => $newPlainPassword]);

        $this->info("Password updated for [{$user->email}].");

        return self::SUCCESS;
    }
}
