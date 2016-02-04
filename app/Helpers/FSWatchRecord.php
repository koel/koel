<?php

namespace App\Helpers;

class FSWatchRecord
{
    /**
     * The event separator used in our fswatch command.
     */
    const FSWATCH_FLAG_SEPARATOR = '::';

    /**
     * Path of the file/directory that triggers the fswatch event.
     *
     * @var string
     */
    protected $path;

    /**
     * The flags of the fswatch event.
     *
     * @var array
     */
    protected $eventFlags;

    protected $watchedEvents = ['Created', 'Removed', 'Renamed', 'Updated'];

    /**
     * Construct an FSWatchRecord object for a record string.
     *
     * @param string $string The record string, e.g.
     *                       "/full/path/to/changed/file Renamed::IsFile"
     */
    public function __construct($string)
    {
        $parts = explode(' ', $string);
        $this->eventFlags = explode(self::FSWATCH_FLAG_SEPARATOR, array_pop($parts));
        $this->path = implode(' ', $parts);
    }

    /**
     * Determine if the event is valid to Koel.
     * We only watch Created, Removed, Renamed, and Updated events.
     *
     * @return bool
     */
    public function isValidEvent()
    {
        foreach ($this->watchedEvents as $e) {
            if (in_array($e, $this->eventFlags)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the file/directory is deleted from the system.
     * We can't rely on fswatch, since the event is OS-dependent.
     * For example, deleting on OSX will be reported as "Renamed", as
     * the file/directory is "renamed" into the Trash folder.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return !file_exists($this->path);
    }

    /**
     * Determine if the changed object is a file.
     *
     * @return bool
     */
    public function isFile()
    {
        return is_file($this->path);
    }

    /**
     * Determine if the changed object is a directory.
     *
     * @return bool
     */
    public function isDir()
    {
        return is_dir($this->path);
    }

    /**
     * Get the full path of the changed file/directory.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the event flags of the fswatch record.
     *
     * @return array
     */
    public function getEventFlags()
    {
        return $this->eventFlags;
    }
}
