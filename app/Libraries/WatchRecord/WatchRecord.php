<?php

namespace App\Libraries\WatchRecord;

abstract class WatchRecord implements WatchRecordInterface
{
    /**
     * Array of the occurred events.
     *
     * @var array
     */
    protected $events;

    /**
     * Full path of the file/directory on which the event occurred.
     *
     * @var string
     */
    protected $path;

    /**
     * The input of the watch record.
     * For example, an inotifywatch record should have an input similar to
     * "DELETE /var/www/media/song.mp3".
     *
     * @var string
     */
    protected $input;

    /**
     * @param string $input The output from a watcher command (which is an input for our script)
     */
    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function isFile(): bool
    {
        return !$this->isDirectory();
    }

    /**
     * Check if a given event name exists in the event array.
     */
    protected function eventExists(string $event): bool
    {
        return in_array($event, $this->events, true);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function __toString(): string
    {
        return $this->input;
    }
}
