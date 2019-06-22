<?php

namespace Tests\Integration\Services;

use App\Services\FileSynchronizer;
use Tests\TestCase;

class FileSynchronizerTest extends TestCase
{
    /** @var FileSynchronizer */
    private $fileSynchronizer;

    public function setUp()
    {
        parent::setUp();
        $this->fileSynchronizer = app(FileSynchronizer::class);
    }

    public function testGetFileInfo()
    {
        $info = $this->fileSynchronizer->setFile(__DIR__.'/../../songs/full.mp3')->getFileInfo();

        $expectedData = [
            'artist' => 'Koel',
            'album' => 'Koel Testing Vol. 1',
            'compilation' => false,
            'title' => 'Amet',
            'track' => 5,
            'disc' => 3,
            'lyrics' => "Foo\rbar",
            'cover' => [
                'data' => file_get_contents(__DIR__.'/../../blobs/cover.png'),
                'image_mime' => 'image/png',
                'image_width' => 512,
                'image_height' => 512,
                'imagetype' => 'PNG',
                'picturetype' => 'Other',
                'description' => '',
                'datalength' => 7627,
            ],
            'path' => __DIR__.'/../../songs/full.mp3',
            'mtime' => filemtime(__DIR__.'/../../songs/full.mp3'),
            'albumartist' => '',
        ];

        self::assertArraySubset($expectedData, $info);
        self::assertEquals(10.083, $info['length'], '', 0.001);
    }

    /** @test */
    public function song_without_a_title_tag_has_file_name_as_the_title()
    {
        $this->fileSynchronizer->setFile(__DIR__.'/../../songs/blank.mp3');
        self::assertSame('blank', $this->fileSynchronizer->getFileInfo()['title']);
    }
}
