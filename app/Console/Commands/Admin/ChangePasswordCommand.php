<?php

namespace App\Console\Commands\Admin;

use App\Console\Commands\Concerns\AskForPassword;
use App\Repositories\UserRepository;
use Illuminate\Console\Command;
use Illuminate\Contracts\Hashing\Hasher as Hash;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class ChangePasswordCommand extends Command
{
    use AskForPassword;

    protected $signature = "koel:admin:change-password
                            {email? : The user's email. If empty, will get the default admin user.}";
    protected $description = "Change a user's password";

    public function __construct(
        private readonly Hash $hash,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = $email ? $this->userRepository->findOneByEmail($email) : $this->userRepository->getOrCreateFirstAdmin();

        if (!$user) {
            error('The user account cannot be found.');

            return self::FAILURE;
        }

        info("Changing the user's password (ID: {$user->id}, email: $user->email)");

        $user->password = $this->hash->make($this->askForPassword());
        $user->save();

        info('Alrighty, the new password has been saved. Enjoy!');

        return self::SUCCESS;
    }
}
