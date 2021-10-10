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
                        'rules' => [
                            [
                                'model' => 'foo',
                                'operator' => 'like',
                                'value' => ['bar'],
                            ],
                        ],
                    ],
                ],
            ],
            'invalid operator' => [
                [
                    [
                        'rules' => [
                            [
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
                        'rules' => [
                            [
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
                        'rules' => [
                            [
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
                        'rules' => [
                            [
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
                        'rules' => [
                            [
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
                        'rules' => [
                            [
                                'model' => 'artist.name',
                                'operator' => 'is',
                                'value' => ['bar'],
                            ],
                            [
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
                        'rules' => [
                            [
                                'model' => 'artist.name',
                                'operator' => 'is',
                                'value' => ['bar'],
                            ],
                            [
                                'model' => 'interactions.play_count',
                                'operator' => 'isGreaterThan',
                                'value' => [50],
                            ],
                        ],
                    ],
                    [
                        'rules' => [
                            [
                                'model' => 'album.name',
                                'operator' => 'contains',
                                'value' => ['bar'],
                            ],
                            [
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
