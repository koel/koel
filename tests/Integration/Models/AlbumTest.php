<?php

namespace Tests\Integration\Models;

use App\Models\Album;
use App\Models\Artist;
use Lastfm;
use org\bovigo\vfs\vfsStream;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    /** @test */
    public function extra_info_can_be_retrieved_for_an_album()
    {
        // Given there's an album
        /** @var Album $album */
        $album = factory(Album::class)->create();

        // When I get the extra info for the album
        Lastfm::shouldReceive('getAlbumInfo')
            ->once()
            ->with($album->name, $album->artist->name)
            ->andReturn(['foo' => 'bar']);

        $info = $album->getInfo();

        // Then I receive the extra info
        $this->assertEquals(['foo' => 'bar'], $info);
    }

    /** @test */
    public function exist_album_can_be_retrieved_using_artist_and_name()
    {
        // Given there's an existing album from an artist
        /** @var Artist $artist */
        $artist = factory(Artist::class)->create();
        /** @var Album $album */
        $album = factory(Album::class)->create([
            'artist_id' => $artist->id,
        ]);

        // When I try to get the album by artist and name
        $gottenAlbum = Album::get($artist, $album->name);

        // Then I get the album
        $this->assertSame($album->id, $gottenAlbum->id);
    }

    /** @test */
    public function new_album_can_be_created_using_artist_and_name()
    {
        // Given an artist and an album name
        $artist = factory(Artist::class)->create();
        $name = 'Foo';

        // And an album with such details doesn't exist yet
        $this->assertNull(Album::whereArtistIdAndName($artist->id, $name)->first());

        // When I try to get the album by such artist and name
        $album = Album::get($artist, $name);

        // Then I get the new album
        $this->assertNotNull($album);
    }

    /** @test */
    public function new_album_without_a_name_is_created_as_unknown_album()
    {
        // Given an album without a name
        $name = '';

        // When we create such an album
        $album = Album::get(factory(Artist::class)->create(), $name);

        // Then the album's name is "Unknown Album"
        $this->assertEquals('Unknown Album', $album->name);
    }

    /** @test */
    public function new_album_is_created_with_artist_as_various_if_is_compilation_flag_is_true()
    {
        // Given we create a new album with $isCompilation flag set to TRUE
        $isCompilation = true;

        // When the album is created
        $album = Album::get(factory(Artist::class)->create(), 'Foo', $isCompilation);

        // Then its artist is Various Artist
        $this->assertTrue($album->artist->is_various);
    }

    /** @test */
    public function it_can_write_a_cover_file_and_update_itself_with_the_cover_file()
    {
        // Given there's an album and a cover file content
        /** @var Album $album */
        $album = factory(Album::class)->create();
        $coverContent = 'dummy';
        $root = vfsStream::setup('home');
        $coverPath = vfsStream::url('home/foo.jpg');

        // When I call the method to write the cover file
        $album->writeCoverFile($coverContent, 'jpg', $coverPath);

        // Then I see the cover file is generated
        $this->assertTrue($root->hasChild('foo.jpg'));

        // And the album's cover attribute is updated
        $this->assertEquals('http://localhost/public/img/covers/foo.jpg', Album::find($album->id)->cover);
    }

    /** @test */
    public function it_can_copy_a_cover_file_and_update_itself_with_the_cover_file()
    {
        // Given there's an album and an original image file
        /** @var Album $album */
        $album = factory(Album::class)->create();
        $root = vfsStream::setup('home');
        $imageFile = vfsStream::newFile('foo.jpg')->at($root)->setContent('foo');
        $coverPath = vfsStream::url('home/bar.jpg');

        // When I call the method to copy the image file as the cover file
        $album->copyCoverFile($imageFile->url(), $coverPath);

        // Then I see the cover file is copied
        $this->assertTrue($root->hasChild('bar.jpg'));

        // And the album's cover attribute is updated
        $this->assertEquals('http://localhost/public/img/covers/bar.jpg', Album::find($album->id)->cover);
    }
}
