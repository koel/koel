<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\DotenvEditor;

class GenerateJwtSecretCommand extends Command
{
    protected $name = 'koel:generate-jwt-secret';
    protected $description = 'Set the JWTAuth secret key used to sign the tokens';
    private $dotenvEditor;

    public function __construct(DotenvEditor $dotenvEditor)
    {
        parent::__construct();

        $this->dotenvEditor = $dotenvEditor;
    }

    public function handle()
    {
        if (config('jwt.secret')) {
            $this->comment('JWT secret exists -- skipping');

            return;
        }

        $this->info('Generating JWT secret');
        $this->dotenvEditor->setKey('JWT_SECRET', Str::random(32))->save();
    }
}
