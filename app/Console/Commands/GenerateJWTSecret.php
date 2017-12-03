<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class GenerateJWTSecret extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'koel:generate-jwt-secret';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the JWTAuth secret key used to sign the tokens';

    /**
     * Execute the console command.
     *
     * @throws \RuntimeException
     */
    public function handle()
    {
        if (config('jwt.secret')) {
            $this->comment('JWT secret exists -- skipping');

            return;
        }

        $this->info('Generating JWT secret');
        DotenvEditor::setKey('JWT_SECRET', Str::random(32))->save();
    }
}
