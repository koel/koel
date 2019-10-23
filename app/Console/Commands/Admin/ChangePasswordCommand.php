<?php

namespace App\Console\Commands\Admin;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Hashing\Hasher as Hash;

class ChangePasswordCommand extends Command
{
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

        do {
            $password = $this->secret('New password');
            $confirmedPassword = $this->secret('Again, just to be sure');
        } while (!$this->validatePasswords($password, $confirmedPassword));

        $user->password = $this->hash->make($password);
        $user->save();

        $this->comment('New password saved, enjoy! 👌');
    }

    private function validatePasswords(?string $password, ?string $confirmedPassword): bool
    {
        if (!$password || !$confirmedPassword) {
            $this->error('Passwords cannot be empty. You know that.');

            return false;
        }

        if (strcmp($password, $confirmedPassword) !== 0) {
            $this->error('The passwords do not match. Try again maybe?');

            return false;
        }

        return true;
    }
}
