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
}
