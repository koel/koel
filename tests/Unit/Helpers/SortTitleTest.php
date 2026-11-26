<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SortTitle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SortTitleTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function provideTitles(): array
    {
        return [
            'The' => ['The Song', 'Song, The'],
            'A' => ['A Song', 'Song, A'],
            'An' => ['An Ocean', 'Ocean, An'],
            'Spanish' => ['Los Lobos', 'Lobos, Los'],
            'French' => ['Les Misérables', 'Misérables, Les'],
            'lowercase article' => ['the clock ticks on', 'Clock ticks on, the'],
            'no article' => ['Theory of Everything', 'Theory of Everything'],
            'article without a space after it' => ['A-ha', 'A-ha'],
            'article alone' => ['The', 'The'],
            'surrounding spaces' => ['  The Song  ', 'Song, The'],
        ];
    }

    #[Test]
    #[DataProvider('provideTitles')]
    public function moveTheLeadingArticleToTheEnd(string $title, string $sortTitle): void
    {
        self::assertSame($sortTitle, SortTitle::fromTitle($title));
    }

    #[Test]
    public function limitTheLength(): void
    {
        self::assertSame(SortTitle::MAX_LENGTH, mb_strlen(SortTitle::fromTitle(str_repeat('ê', 300))));
    }
}
