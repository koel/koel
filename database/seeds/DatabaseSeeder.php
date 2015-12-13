<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Model::unguard();

        // This must be run first, to check for dependencies
        $this->call(UserTableSeeder::class);

        $this->call(ArtistTableSeeder::class);
        $this->call(AlbumTableSeeder::class);
        $this->call(SettingTableSeeder::class);

        Model::reguard();
    }
}
