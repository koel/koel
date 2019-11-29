<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(ArtistTableSeeder::class);
        $this->call(AlbumTableSeeder::class);
        $this->call(SettingTableSeeder::class);

        Model::reguard();
    }
}
