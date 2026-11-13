<?php

namespace App\Console\Commands;

use App\Actions\Game\ChangeUserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('grant:admin {email : The email address of the account}')]
#[Description('Give the user with the given email address the admin role')]
class GrantAdmin extends Command
{
    /**
     * Execute the console command.
     *
     * The role change skips the in-game admin check, since whoever can run Artisan already
     * controls the server. The audit row names the account itself as the actor, because
     * there is no signed-in user on the command line.
     */
    public function handle(ChangeUserRole $changeUserRole): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("No user found with email [{$this->argument('email')}].");

            return self::FAILURE;
        }

        if ($user->isAdmin()) {
            $this->info("[{$user->email}] is already an admin.");

            return self::SUCCESS;
        }

        $adminRole = Role::query()->where('key', 'admin')->first();

        if ($adminRole === null) {
            $this->error('The admin role does not exist. Run the migrations first.');

            return self::FAILURE;
        }

        $changeUserRole($user, $user, $adminRole, authorize: false);

        $this->info("[{$user->email}] is now an admin.");

        return self::SUCCESS;
    }
}
