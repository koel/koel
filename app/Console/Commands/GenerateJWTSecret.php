<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

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
     */
    public function fire()
    {
        $key = Str::random(32);

        $path = base_path('.env');
        $content = file_get_contents($path);

        if (strpos($content, 'JWT_SECRET=') !== false) {
            file_put_contents($path, str_replace('JWT_SECRET=', "JWT_SECRET=$key", $content));
        } else {
            file_put_contents($path, $content.PHP_EOL."JWT_SECRET=$key");
        }
    }
}
