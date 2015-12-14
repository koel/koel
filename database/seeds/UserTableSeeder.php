<?php

use App\Models\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (!env('ADMIN_NAME') || !env('ADMIN_EMAIL') || !env('ADMIN_PASSWORD')) {
            $this->command->error('Please fill in initial admin details in .env file first.');
            abort(422);
        }

        User::create([
            'name' => env('ADMIN_NAME'),
            'email' => env('ADMIN_EMAIL'),
            'password' => Hash::make(env('ADMIN_PASSWORD')),
            'is_admin' => true,
        ]);

        if (app()->environment() !== 'testing') {
            $this->command->info('Admin user created. You can (and should) remove the auth details from .env now.');
        }
    }
}
