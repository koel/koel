<?php

namespace App\Console\Commands;

use App\Models\Genre;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDemoGenre extends Command
{
    protected $signature = 'fix-demo-genre';

    protected $description = 'Command description';

    public function handle(): int
    {
        /** @var name => id[] $names */
        $names = [];

        Genre::all()->each(static function (Genre $genre) use (&$names): void {
            if (!array_key_exists($genre->name, $names)) {
                $names[$genre->name] = [];
            }

            $names[$genre->name][] = $genre->id;
        });

        dump($names);

        return 0;

        // for each of $names, the key is the name and the value is an array of ids
        // that share the same name.
        // If there are more than one id, we update the genre_song table
        // to set the genre_id of all but the first one to the first id
        foreach ($names as $name => $ids) {
            $firstId = array_shift($ids);

            if (count($ids) === 0) {
                continue; // No duplicates to fix
            }

            $this->info("Fixing genres with name: {$name} and ids: " . implode(', ', $ids));

            // Update the genre_song table to set the genre_id of all but the first one to the first id
            DB::table('genre_song')->whereIn('genre_id', $ids)->update(['genre_id' => $firstId]);

            // Delete the duplicate genres
            Genre::query()->whereIn('id', $ids)->delete();
        }
    }
}
