<?php

namespace App\Values\Scanning;

use App\Exceptions\InvalidScanTagsException;
use App\Models\User;

final class ScanConfiguration
{
    public const array TAGS = [
        'title',
        'album',
        'artist',
        'albumartist',
        'track',
        'disc',
        'year',
        'genre',
        'lyrics',
        'cover',
    ];

    /**
     * @param User $owner The user who owns the song
     * @param bool $makePublic Whether to make the song public
     * @param array<string> $ignores The tags to ignore/exclude (only taken into account if the song already exists)
     * @param bool $force Whether to force syncing, even if the file is unchanged
     * @param bool $extractFolderStructure Whether to extract the folder structure from the file path.
     * This should be set to true for the local storage driver, and false for cloud storage drivers.
     */
    private function __construct(
        public User $owner,
        public bool $makePublic,
        public array $ignores,
        public bool $force,
        public bool $extractFolderStructure,
    ) {}

    public static function make(
        User $owner,
        bool $makePublic = false,
        array $ignores = [],
        bool $force = false,
        bool $extractFolderStructure = true,
    ): self {
        return new self($owner, $makePublic, $ignores, $force, $extractFolderStructure);
    }

    /**
     * @param array<string> $ignoredTags
     * @param array<string> $onlyTags
     *
     * @return array<string>
     */
    public static function resolveIgnoredTags(array $ignoredTags, array $onlyTags): array
    {
        if (!$onlyTags) {
            return $ignoredTags;
        }

        throw_if($ignoredTags, InvalidScanTagsException::bothIgnoredAndOnly());

        $unknownTags = array_values(array_diff($onlyTags, self::TAGS));

        throw_if($unknownTags, InvalidScanTagsException::unknownTags($unknownTags));

        return collect(self::TAGS)->diff($onlyTags)->sort()->values()->all();
    }
}
