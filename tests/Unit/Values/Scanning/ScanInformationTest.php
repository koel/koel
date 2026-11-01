<?php

namespace Tests\Unit\Values\Scanning;

use App\Models\Artist;
use App\Values\Scanning\ScanInformation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class ScanInformationTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function provideCompilationFlagKeys(): array
    {
        return [
            'ID3 TCMP' => ['part_of_a_compilation'],
            'Vorbis COMPILATION and MP4 cpil' => ['compilation'],
        ];
    }

    #[Test]
    #[DataProvider('provideCompilationFlagKeys')]
    public function compilationWithoutAlbumArtistBelongsToVariousArtists(string $flagKey): void
    {
        $info = ScanInformation::fromGetId3Info([
            'comments' => [
                'title' => ['Track'],
                'artist' => ['Track Artist'],
                'album' => ['Compilation'],
                $flagKey => ['1'],
            ],
        ], test_path('songs/full-vorbis-comments.flac'));

        self::assertSame(Artist::VARIOUS_NAME, $info->albumArtistName);
    }

    #[Test]
    public function compilationFlagSetToFalseIsNotACompilation(): void
    {
        $info = ScanInformation::fromGetId3Info([
            'comments' => [
                'title' => ['Track'],
                'artist' => ['Track Artist'],
                'album' => ['Album'],
                'compilation' => ['false'],
            ],
        ], test_path('songs/full-vorbis-comments.flac'));

        self::assertSame('', $info->albumArtistName);
    }

    #[Test]
    public function readTheDiscNumberOfAnM4aFile(): void
    {
        $info = ScanInformation::fromGetId3Info([
            'comments' => ['title' => ['Track'], 'disc_number' => ['2/2']],
        ], test_path('songs/full.mp3'));

        self::assertSame(2, $info->disc);
    }

    /** @return array<string, array{string, int, ?string}> */
    public static function provideTrackTags(): array
    {
        return [
            'number' => ['5', 5, null],
            'number with total' => ['5/12', 5, null],
            'vinyl side and number' => ['B2', 2, 'B2'],
            'vinyl side and number with total' => ['A1/12', 1, 'A1'],
            'vinyl double-letter side' => ['AA1', 1, 'AA1'],
            'vinyl side only' => ['C', 0, 'C'],
            'text that is not a vinyl label' => ['Bonus Track', 0, null],
        ];
    }

    #[Test]
    #[DataProvider('provideTrackTags')]
    public function readTheTrackNumberAndVinylLabel(string $tag, int $track, ?string $label): void
    {
        $info = ScanInformation::fromGetId3Info([
            'comments' => ['title' => ['Track'], 'track_number' => [$tag]],
        ], test_path('songs/full.mp3'));

        self::assertSame($track, $info->track);
        self::assertSame($label, $info->trackLabel);
    }

    #[Test]
    public function readAnAlbumArtistTaggedWithASpace(): void
    {
        $info = ScanInformation::fromGetId3Info([
            'comments' => ['title' => ['Track'], 'album artist' => ['Various Artists']],
        ], test_path('songs/full-vorbis-comments.flac'));

        self::assertSame('Various Artists', $info->albumArtistName);
    }

    #[Test]
    public function hashTheFileWithXxh128(): void
    {
        $path = test_path('songs/full-vorbis-comments.flac');

        $info = ScanInformation::fromGetId3Info(['comments' => ['title' => ['Track']]], $path);

        self::assertSame(hash_file('xxh128', $path), $info->hash);
    }
}
