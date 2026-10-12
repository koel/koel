<?php

namespace App\Console\Commands\Admin;

use App\Console\Commands\Concerns\AskForPassword;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Console\Command;

class ChangePasswordCommand extends Command
{
    use AskForPassword;

    protected $signature = "koel:admin:change-password
                            {email? : The user's email. If empty, will get the default admin user.}";
    protected $description = "Change a user's password";

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserService $userService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = $email ? $this->userRepository->findOneByEmail($email) : $this->userRepository->getOrCreateFirstAdmin();

        if (!$user) {
            $this->error('The user account cannot be found.');

            return self::FAILURE;
        }

        $this->comment("Changing the user's password (ID: {$user->id}, email: $user->email)");

        $this->userService->changePassword($user, $this->askForPassword());

        $this->comment('Alrighty, the new password has been saved. Enjoy! 👌');

        return self::SUCCESS;
    }
}
