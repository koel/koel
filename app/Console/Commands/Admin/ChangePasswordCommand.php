<?php

namespace App\Console\Commands\Admin;

use App\Console\Commands\Traits\AskForPassword;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Hashing\Hasher as Hash;

class ChangePasswordCommand extends Command
{
    use AskForPassword;

    protected $name = 'koel:admin:change-password';
    protected $description = "Change the default admin's password";

    private $hash;

    public function __construct(Hash $hash)
    {
        parent::__construct();
        $this->hash = $hash;
    }

    public function handle(): void
    {
        /** @var User|null $user */
        $user = User::where('is_admin', true)->first();

        if (!$user) {
            $this->error('An admin account cannot be found. Have you set up Koel yet?');

            return;
        }

        $this->comment("Changing the default admin's password (ID: {$user->id}, email: {$user->email})");

        $user->password = $this->hash->make($this->askForPassword());
        $user->save();

        $this->comment('Alrighty, your new password has been saved. Enjoy! 👌');
    }
}
