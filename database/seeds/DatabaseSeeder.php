<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Model::unguard();

        // This must be run first, to check for dependencies
//        $this->call(UserTableSeeder::class);

        $this->call(ArtistTableSeeder::class);
        $this->call(AlbumTableSeeder::class);
        $this->call(SettingTableSeeder::class);

        Model::reguard();
    }
}
