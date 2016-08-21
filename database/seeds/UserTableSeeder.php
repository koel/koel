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
        if (!config('koel.admin.name') ||
            !config('koel.admin.email') ||
            !config('koel.admin.password')) {
            $this->command->error('Please fill in initial admin details in .env file first.');
            abort(422);
        }

        User::create([
            'name' => config('koel.admin.name'),
            'email' => config('koel.admin.email'),
            'password' => Hash::make(config('koel.admin.password')),
            'is_admin' => true,
        ]);

        if (app()->environment() !== 'testing') {
            $this->command->info('Admin user created. You can (and should) remove the auth details from .env now.');
        }
    }
}
