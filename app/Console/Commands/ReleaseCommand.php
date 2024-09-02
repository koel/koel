<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PHLAK\SemVer\Version;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class ReleaseCommand extends Command
{
    protected $signature
        = 'koel:release {version? : The version to release, or "patch", "minor", "major" for auto-increment}';
    protected $description = 'Tag and release a new version of Koel';

    private Version $currentVersion;

    public function handle(): int
    {
        self::ensureCleanWorkingDirectory();

        $this->getCurrentVersion();

        $releaseVersion = match ($this->argument('version')) {
            'patch' => (clone $this->currentVersion)->incrementPatch()->prefix(),
            'minor' => (clone $this->currentVersion)->incrementMinor()->prefix(),
            'major' => (clone $this->currentVersion)->incrementMajor()->prefix(),
            null => $this->acquireReleaseVersionInteractively(),
            default => self::tryParseVersion($this->argument('version')) ?? $this->acquireReleaseVersionInteractively(),
        };

        try {
            $this->release($releaseVersion);
        } catch (Throwable $e) {
            error($e->getMessage());
            warning('Something went wrong. Double-check the working directory and maybe try again.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function release(string $version): void
    {
        // Ensure the version is prefixed.
        $version = Version::parse($version)->prefix();

        info("Releasing version $version...");

        File::put(base_path('.version'), $version);

        $gitCommands = [
            'add .',
            "commit -m 'chore(release): bump version to $version'",
            'push',
            "tag $version",
            'tag latest -f',
            'push origin --tags -f',
            'checkout release',
            'pull',
            'merge master',
            'push',
        ];

        foreach ($gitCommands as $command) {
            $this->components->task("Executing `git $command`", static fn () => self::runOkOrThrow("git $command"));
        }

        info("Success! The new version $version has been tagged.");
        info('Now go to https://github.com/koel/koel/releases and finish the draft release notes.');
    }

    private function acquireReleaseVersionInteractively(): string
    {
        $patchVersion = (clone $this->currentVersion)->incrementPatch()->prefix();

        $suggestedVersions = [
            $patchVersion => 'Patch',
            (clone $this->currentVersion)->incrementMinor()->prefix() => 'Minor',
            (clone $this->currentVersion)->incrementMajor()->prefix() => 'Major',
        ];

        $options = [];

        foreach ($suggestedVersions as $version => $name) {
            $options[$version] = "$name -> $version";
        }

        $options['custom'] = 'Custom';

        $selected = select(
            label: 'What are we releasing?',
            options: $options,
            default: $patchVersion,
        );

        if ($selected === 'custom') {
            $selected = text(
                label: 'Enter the version you want to release',
                placeholder: $patchVersion,
                required: true,
                validate: static fn (string $value) => self::tryParseVersion($value)
                    ? null
                    : 'Invalid version format',
            );
        }

        return Version::parse($selected)->prefix();
    }

    private static function tryParseVersion(string $version): ?string
    {
        try {
            return Version::parse($version)->prefix();
        } catch (Throwable) {
            return null;
        }
    }

    public function getCurrentVersion(): void
    {
        $this->currentVersion = new Version(File::get(base_path('.version')));

        note('Current version: ' . $this->currentVersion->prefix());
    }

    private static function ensureCleanWorkingDirectory(): void
    {
        if (Process::run('git status --porcelain')->output()) {
            error('Your working directly is not clean. Please commit or stash your changes before proceeding.');

            exit(self::FAILURE);
        }
    }

    private static function runOkOrThrow(string $command): void
    {
        throw_unless(Process::forever()->run($command)->successful());
    }
}
