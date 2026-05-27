<?php

namespace App\Console\Commands\Subsonic;

use App\Repositories\UserRepository;
use Illuminate\Console\Command;

class ShowApiKeyCommand extends Command
{
    protected $signature = 'koel:subsonic:apikey {email}';
    protected $description = "Show a user's Subsonic API key";

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $user = $this->userRepository->findOneByEmail($this->argument('email'));

        if (!$user) {
            $this->error('The user account cannot be found.');

            return self::FAILURE;
        }

        $this->line($user->subsonic_api_key);

        return self::SUCCESS;
    }
}
