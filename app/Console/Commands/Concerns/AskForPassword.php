<?php

namespace App\Console\Commands\Concerns;

use SensitiveParameter;

use function Laravel\Prompts\error;
use function Laravel\Prompts\password;

trait AskForPassword
{
    private function askForPassword(): string
    {
        do {
            $password = password('Your desired password');

            if (!$password) {
                error('Passwords cannot be empty. You know that.');
                continue;
            }

            $confirmedPassword = password('Again, just to be sure');
        } while (!$this->comparePasswords($password, $confirmedPassword ?? null));

        return $password;
    }

    private function comparePasswords(
        #[SensitiveParameter] ?string $password,
        #[SensitiveParameter] ?string $confirmedPassword,
    ): bool {
        if (!$password || !$confirmedPassword) {
            return false;
        }

        if (strcmp($password, $confirmedPassword) !== 0) {
            error('The passwords do not match. Try again maybe?');

            return false;
        }

        return true;
    }
}
