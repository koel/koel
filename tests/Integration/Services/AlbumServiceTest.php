<?php

namespace Tests\Integration\Services;

use App\Enums\ImageChangeType;
use App\Exceptions\AlbumNameConflictException;
use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Favorite;
use App\Models\Rating;
use App\Models\Song;
use App\Services\AlbumService;
use App\Services\Image\ImageStorage;
use App\Values\Album\AlbumUpdateData;
use App\Values\ImageChange;
use App\Values\ImageWritingConfig;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\minimal_base64_encoded_image;
use function Tests\test_path;

class AlbumServiceTest extends TestCase
{
    private AlbumService $service;
    private ImageStorage|MockInterface $imageStorage;

    public function setUp(): void
    {
        parent::setUp();

        $this->imageStorage = $this->mock(ImageStorage::class);
        $this->service = app(AlbumService::class);
    }

    #[Test]
    public function giveEveryAlbumInAFolderTheFolderCover(): void
    {
        $directory = sys_get_temp_dir() . '/koel-cover-' . uniqid();
        File::ensureDirectoryExists($directory);
        File::copy(test_path('fixtures/cover.png'), "$directory/cover.png");
        [$firstAlbum, $secondAlbum] = Album::factory()->createMany(2)->all();
        $this->imageStorage->expects('storeImage')->twice()->with("$directory/cover.png")->andReturn('cover.webp');

        $this->service->trySetAlbumCoverFromDirectory($firstAlbum, $directory);
        $this->service->trySetAlbumCoverFromDirectory($secondAlbum, $directory);

        self::assertSame('cover.webp', $secondAlbum->refresh()->getRawOriginal('cover'));
        File::deleteDirectory($directory);
    }

    #[Test]
    public function findTheFolderCoverAgainAfterItIsRenamed(): void
    {
        $directory = sys_get_temp_dir() . '/koel-cover-' . uniqid();
        File::ensureDirectoryExists($directory);
        File::copy(test_path('fixtures/cover.png'), "$directory/cover.png");
        [$firstAlbum, $secondAlbum] = Album::factory()->createMany(2)->all();
        $this->imageStorage->expects('storeImage')->with("$directory/cover.png")->andReturn('cover.webp');
        $this->imageStorage->expects('storeImage')->with("$directory/folder.png")->andReturn('folder.webp');

        $this->service->trySetAlbumCoverFromDirectory($firstAlbum, $directory);
        File::move("$directory/cover.png", "$directory/folder.png");
        $this->service->trySetAlbumCoverFromDirectory($secondAlbum, $directory);

        self::assertSame('folder.webp', $secondAlbum->refresh()->getRawOriginal('cover'));
        File::deleteDirectory($directory);
    }

    #[Test]
    public function updateAlbum(): void
    {
        $album = Album::factory()->createOne([
            'name' => 'Old Album Name',
            'year' => 2020,
        ]);

        $songs = Song::factory()->for($album)->count(2)->create();

        $data = AlbumUpdateData::make(name: 'New Album Name', year: 2023);

        $this->imageStorage
            ->expects('storeOptionalImage')
            ->with(null)
            ->andReturn(ImageChange::make(ImageChangeType::KEEP));

        $updatedAlbum = $this->service->updateAlbum($album, $data);

        self::assertEquals('New Album Name', $updatedAlbum->name);
        self::assertEquals(2023, $updatedAlbum->year);

        $songs->each(static function (Song $song) use ($updatedAlbum): void {
            self::assertEquals($updatedAlbum->name, $song->fresh()->album_name);
        });
    }

    #[Test]
    public function updateAlbumWithCover(): void
    {
        $album = Album::factory()->createOne([
            'name' => 'Old Album Name',
            'year' => 2020,
        ]);

        $songs = Song::factory()->for($album)->count(2)->create();

        $data = AlbumUpdateData::make(name: 'New Album Name', year: 2023, cover: minimal_base64_encoded_image());

        $ulid = Ulid::freeze();
        $this->imageStorage
            ->expects('storeOptionalImage')
            ->with(minimal_base64_encoded_image())
            ->andReturn(ImageChange::make(ImageChangeType::REPLACE, "$ulid.webp"));

        $updatedAlbum = $this->service->updateAlbum($album, $data);

        self::assertEquals('New Album Name', $updatedAlbum->name);
        self::assertEquals(2023, $updatedAlbum->year);
        self::assertEquals("$ulid.webp", $updatedAlbum->cover);

        $songs->each(static function (Song $song) use ($updatedAlbum): void {
            self::assertEquals($updatedAlbum->name, $song->fresh()->album_name);
        });
    }

    #[Test]
    public function updateAlbumRemovingCover(): void
    {
        $album = Album::factory()->createOne();

        $data = AlbumUpdateData::make(name: 'New Album Name', year: 2023, cover: '');

        $this->imageStorage
            ->expects('storeOptionalImage')
            ->with('')
            ->andReturn(ImageChange::make(ImageChangeType::REMOVE));

        $updatedAlbum = $this->service->updateAlbum($album, $data);

        self::assertEquals('New Album Name', $updatedAlbum->name);
        self::assertEquals(2023, $updatedAlbum->year);
        self::assertEmpty($updatedAlbum->cover);
    }

    #[Test]
    public function rejectUpdatingIfArtistAlreadyHasAnAlbumWithTheSameName(): void
    {
        $existingAlbum = Album::factory()->createOne(['name' => 'Existing Album Name']);
        $album = Album::factory()->for($existingAlbum->artist)->createOne(['name' => 'Old Album Name']);
        $data = AlbumUpdateData::make(name: 'existing album name', year: 2023);

        $this->expectException(AlbumNameConflictException::class);

        $this->service->updateAlbum($album, $data);
    }

    #[Test]
    public function rejectUpdatingToTheExactNameOfAnAlbumWithNonAsciiCharacters(): void
    {
        $existingAlbum = Album::factory()->createOne(['name' => 'Über Album']);
        $album = Album::factory()->for($existingAlbum->artist)->createOne(['name' => 'Old Album Name']);

        $this->expectException(AlbumNameConflictException::class);

        $this->service->updateAlbum($album, AlbumUpdateData::make(name: 'Über Album', year: 2023));
    }

    #[Test]
    public function rejectUpdatingToTheNameOfAnAlbumThatDiffersOnlyInCase(): void
    {
        $album = Album::factory()->createOne(['name' => 'black album']);
        Album::factory()->for($album->artist)->createOne(['name' => 'Black Album']);

        $this->expectException(AlbumNameConflictException::class);

        $this->service->updateAlbum($album, AlbumUpdateData::make(name: 'Black Album', year: 2023));
    }

    #[Test]
    public function updateAlbumChangingOnlyTheCaseOfItsName(): void
    {
        $album = Album::factory()->createOne(['name' => 'black album']);

        $this->imageStorage
            ->expects('storeOptionalImage')
            ->with(null)
            ->andReturn(ImageChange::make(ImageChangeType::KEEP));

        $updatedAlbum = $this->service->updateAlbum($album, AlbumUpdateData::make(name: 'Black Album', year: 2023));

        self::assertSame('Black Album', $updatedAlbum->name);
    }

    #[Test]
    public function mergeAlbums(): void
    {
        $target = Album::factory()->createOne(['name' => 'Black Album', 'cover' => 'target-cover.webp']);
        $source = Album::factory()->for($target->artist)->createOne(['name' => 'black album']);
        $songs = Song::factory()->for($source)->createMany(2);
        $favorite = Favorite::factory()->for($source, 'favoriteable')->createOne();
        $rating = Rating::factory()->for($source, 'rateable')->createOne();

        $mergedAlbum = $this->service->mergeAlbums($source, $target);

        self::assertTrue($mergedAlbum->is($target));
        self::assertModelMissing($source);
        self::assertSame('target-cover.webp', $mergedAlbum->getRawOriginal('cover'));

        $songs->each(static function (Song $song) use ($target): void {
            $song->refresh();
            self::assertTrue($song->album->is($target));
            self::assertSame('Black Album', $song->album_name);
        });

        self::assertTrue($target->favorites()->whereKey($favorite->id)->exists());
        self::assertTrue($target->ratings()->whereKey($rating->id)->exists());
    }

    #[Test]
    public function storeAlbumCover(): void
    {
        $album = Album::factory()->createOne();

        $this->imageStorage->expects('storeImage')->with('dummy-src')->andReturn('foo.webp');

        $this->service->storeAlbumCover($album, 'dummy-src');

        self::assertSame('foo.webp', $album->refresh()->cover);
    }

    #[Test]
    public function generateAlbumSmallCover(): void
    {
        $album = Album::factory()->createOne(['cover' => 'cover.webp', 'small_cover' => null]);

        $this->imageStorage->expects('get')->with('cover.webp')->andReturn('cover-bytes');

        $this->imageStorage
            ->expects('storeImage')
            ->with('cover-bytes', Mockery::on(static fn (ImageWritingConfig $config) => $config->maxWidth === 144))
            ->andReturn('small.webp');

        $this->service->generateAlbumSmallCover($album);

        self::assertSame('small.webp', $album->refresh()->small_cover);
    }
}
