<?php

namespace App\Console\Commands\Admin;

use App\Enums\Acl\Role;
use App\Repositories\UserRepository;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;

class SetUserRoleCommand extends Command
{
    protected $signature = "koel:admin:set-user-role {email : The user's email}";
    protected $description = 'Set a user\'s role';

    public function __construct(private readonly UserRepository $userRepository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $user = $this->userRepository->findOneByEmail($this->argument('email'));

        if (!$user) {
            $this->components->error('The user account cannot be found.');

            return self::FAILURE;
        }

        $roles = [];

        Role::allAvailable()->each(static function (Role $role) use (&$roles): void {
            $roles[$role->value] = $role->label();
        });

        $role = select(
            label: 'What role should the user have?',
            options: $roles,
            default: $user->role->value,
        );

        $user->syncRoles($role);
        $this->info("The user's role has been set to <info>'$role'</info>.");

        return self::SUCCESS;
    }
}
