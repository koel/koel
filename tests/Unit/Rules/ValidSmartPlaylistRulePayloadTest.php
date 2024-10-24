<?php

namespace Tests\Unit\Rules;

use App\Rules\ValidSmartPlaylistRulePayload;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValidSmartPlaylistRulePayloadTest extends TestCase
{
    /** @return array<mixed> */
    public static function provideInvalidPayloads(): array
    {
        return [
            'invalid format' => ['foo'],
            'invalid model' => [
                [
                    [
                        'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                        'rules' => [
                            [
                                'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
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
                        'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                        'rules' => [
                            [
                                'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
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
                        'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                        'rules' => [
                            [
                                'id' => 'f5fcc10f-eb6a-40f6-baf9-db573de088f8',
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
                        'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                        'rules' => [
                            [
                                'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
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
                        'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                        'rules' => [
                            [
                                'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
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

    #[DataProvider('provideInvalidPayloads')]
    #[Test]
    public function invalidCases($value): void
    {
        $this->expectExceptionMessage('Invalid smart playlist rules');

        $fail = static fn (string $message) => throw new Exception($message);
        (new ValidSmartPlaylistRulePayload())->validate('rules', $value, $fail);
    }

    /** @return array<mixed> */
    public static function provideValidPayloads(): array
    {
        return [
            'one rule' => [
                [
                    [
                        'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                        'rules' => [
                            [
                                'id' => '45368b8f-fec8-4b72-b826-6b295af0da65',
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
                        'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                        'rules' => [
                            [
                                'id' => '45368b8f-fec8-4b72-b826-6b295af0da65',
                                'model' => 'artist.name',
                                'operator' => 'is',
                                'value' => ['bar'],
                            ],
                            [
                                'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
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
                        'id' => '54989bb8-9e4f-4f6e-a28f-320834f9435e',
                        'rules' => [
                            [
                                'id' => '59e95d10-e297-4f33-b2d8-de55e64a02fa',
                                'model' => 'artist.name',
                                'operator' => 'is',
                                'value' => ['bar'],
                            ],
                            [
                                'id' => 'fefa409c-5539-4612-949f-47f71d06c828',
                                'model' => 'interactions.play_count',
                                'operator' => 'isGreaterThan',
                                'value' => [50],
                            ],
                        ],
                    ],
                    [
                        'id' => '45b23131-ece6-4461-8c1b-4d865f06a395',
                        'rules' => [
                            [
                                'id' => 'e3e2f1cc-bde1-43fc-9fb2-96ea7d64412c',
                                'model' => 'album.name',
                                'operator' => 'contains',
                                'value' => ['bar'],
                            ],
                            [
                                'id' => '39bba5c4-e9cb-4b72-a241-6b7c6cc14c3c',
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

    #[DataProvider('provideValidPayloads')]
    #[Test]
    public function validCases($value): void
    {
        (new ValidSmartPlaylistRulePayload())->validate('rules', $value, static fn ($foo) => $foo);
        self::addToAssertionCount(1);
    }
}
