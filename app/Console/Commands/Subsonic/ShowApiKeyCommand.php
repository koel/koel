<?php

namespace App\Console\Commands\Subsonic;

use App\Repositories\UserRepository;
use Illuminate\Console\Command;

class ShowApiKeyCommand extends Command
{
    protected $signature = 'koel:subsonic:apikey {email}';
    protected $description = 'Show a user\'s Subsonic API key';

    public function handle(UserRepository $userRepository): int
    {
        $user = $userRepository->findOneBy(['email' => $this->argument('email')]);

        if (!$user) {
            $this->components->error(sprintf('No user found with email "%s".', $this->argument('email')));

            return self::FAILURE;
        }

        $this->line($user->subsonic_api_key);

        return self::SUCCESS;
    }
}
