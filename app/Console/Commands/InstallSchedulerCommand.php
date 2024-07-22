<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use TiBeN\CrontabManager\CrontabAdapter;
use TiBeN\CrontabManager\CrontabJob;
use TiBeN\CrontabManager\CrontabRepository;

class InstallSchedulerCommand extends Command
{
    protected $signature = 'koel:scheduler:install';
    protected $description = 'Install the scheduler for Koel';

    public function handle(): int
    {
        if (PHP_OS_FAMILY === 'Windows' || PHP_OS_FAMILY === 'Unknown') {
            $this->components->error('This command is only available on Linux systems.');

            return self::FAILURE;
        }

        $crontab = new CrontabRepository(new CrontabAdapter());

        $this->components->info('Trying to install Koel scheduler…');

        if (self::schedulerInstalled($crontab)) {
            $this->components->info('Koel scheduler is already installed. Skipping…');

            return self::SUCCESS;
        }

        $job = CrontabJob::createFromCrontabLine(
            '* * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1'
        );

        $crontab->addJob($job);
        $crontab->persist();

        $this->components->info('Koel scheduler installed successfully.');

        return self::SUCCESS;
    }

    private static function schedulerInstalled(CrontabRepository $crontab): bool
    {
        return (bool) $crontab->findJobByRegex('/artisan schedule:run/');
    }
}
