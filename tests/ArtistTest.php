<?php

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArtistTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
    }

    public function testShouldBeCreatedWithUniqueNames()
    {
        $name = 'Foo Fighters';
        $artist = Artist::get($name);

        $this->assertEquals($name, $artist->name);

        // Should be only 2 records: UNKNOWN_ARTIST, and our Dave Grohl's band
        $this->assertEquals(2, Artist::all()->count());

        Artist::get($name);

        // Should still be 2.
        $this->assertEquals(2, Artist::all()->count());
    }

    public function testArtistWithEmptyNameShouldBeUnknown()
    {
        $this->assertEquals(Artist::UNKNOWN_NAME, Artist::get('')->name);
    }

    public function testNameWithWeirdCharacters()
    {
        // Don't really think this is even necessary if the user has set a proper utf8 encoding
        // for the database.
        $name = '��Ой°Ы&囧rz';
        $artist = factory(Artist::class)->create(['name' => $name]);

        $this->assertEquals($artist->id, Artist::get($name)->id);
    }
}
