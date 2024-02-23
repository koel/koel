<?php

namespace App\Filesystems;

use DateTimeInterface;
use League\Flysystem\Filesystem;
use Spatie\FlysystemDropbox\DropboxAdapter;

class DropboxFilesystem extends Filesystem
{
    public function __construct(private DropboxAdapter $adapter)
    {
        parent::__construct($adapter, ['case_sensitive' => false]);
    }

    public function temporaryUrl(string $path, ?DateTimeInterface $expiresAt = null, array $config = []): string
    {
        return $this->adapter->getUrl($path);
    }

    public function getAdapter(): DropboxAdapter
    {
        return $this->adapter;
    }
}
