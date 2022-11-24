<?php

namespace Tests\Unit\Rules;

use App\Rules\ValidSmartPlaylistRulePayload;
use Tests\TestCase;

class ValidSmartPlaylistRulePayloadTest extends TestCase
{
    /** @return array<mixed> */
    public function provideInvalidPayloads(): array
    {
        return [
            'invalid format' => ['foo'],
            'invalid model' => [
                [
                    [
                        'id' => 1,
                        'rules' => [
                            [
                                'id' => 1,
                                'model' => 'foo',
                                'operator' => 'contains',
                                'value' => ['bar'],
                            ],
                        ],
                    ],
                ],
            ],
            'invalid operator' => [
                [
                    [
                        'id' => 1,
                        'rules' => [
                            [
                                'id' => 1,
                                'model' => 'artist.name',
                                'operator' => '<script>',
                                'value' => ['bar'],
                            ],
                        ],
                    ],
                ],
            ],
            'values are not an array' => [
                [
                    [
                        'id' => 1,
                        'rules' => [
                            [
                                'id' => 1,
                                'model' => 'artist.name',
                                'operator' => 'is',
                                'value' => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
            'values are empty' => [
                [
                    [
                        'id' => 1,
                        'rules' => [
                            [
                                'id' => 1,
                                'model' => 'artist.name',
                                'operator' => 'is',
                                'value' => [],
                            ],
                        ],
                    ],
                ],
            ],
            'values item account exceeds 2' => [
                [
                    [
                        'id' => 1,
                        'rules' => [
                            [
                                'id' => 1,
                                'model' => 'artist.name',
                                'operator' => 'is',
                                'value' => ['bar', 'baz', 'qux'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @dataProvider provideInvalidPayloads */
    public function testInvalidCases($value): void
    {
        self::assertFalse((new ValidSmartPlaylistRulePayload())->passes('rules', $value));
    }

    /** @return array<mixed> */
    public function provideValidPayloads(): array
    {
        return [
            'one rule' => [
                [
                    [
                        'id' => 1,
                        'rules' => [
                            [
                                'id' => 2,
                                'model' => 'artist.name',
                                'operator' => 'is',
                                'value' => ['bar'],
                            ],
                        ],
                    ],
                ],
            ],
            'multiple rules' => [
                [
                    [
                        'id' => 1,
                        'rules' => [
                            [
                                'id' => 1,
                                'model' => 'artist.name',
                                'operator' => 'is',
                                'value' => ['bar'],
                            ],
                            [
                                'id' => 2,
                                'model' => 'interactions.play_count',
                                'operator' => 'isGreaterThan',
                                'value' => [50],
                            ],
                        ],
                    ],
                ],
            ],
            'multiple groups' => [
                [
                    [
                        'id' => 1,
                        'rules' => [
                            [
                                'id' => 1,
                                'model' => 'artist.name',
                                'operator' => 'is',
                                'value' => ['bar'],
                            ],
                            [
                                'id' => 2,
                                'model' => 'interactions.play_count',
                                'operator' => 'isGreaterThan',
                                'value' => [50],
                            ],
                        ],
                    ],
                    [
                        'id' => 2,
                        'rules' => [
                            [
                                'id' => 1,
                                'model' => 'album.name',
                                'operator' => 'contains',
                                'value' => ['bar'],
                            ],
                            [
                                'id' => 2,
                                'model' => 'interactions.play_count',
                                'operator' => 'isBetween',
                                'value' => [10, 100],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @dataProvider provideValidPayloads */
    public function testValidCases($value): void
    {
        self::assertTrue((new ValidSmartPlaylistRulePayload())->passes('rules', $value));
    }
}
