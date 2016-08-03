<?php

namespace App\Libraries\WatchRecord;

class WatchRecord
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
     * WatchRecord constructor.
     *
     * @param $input string The output from a watcher command (which is an input for our script)
     */
    public function __construct($input)
    {
        $this->input = $input;
    }

    /**
     * Determine if the object is a directory.
     *
     * @return bool
     */
    public function isDirectory()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Determine if the object is a file.
     *
     * @return bool
     */
    public function isFile()
    {
        return !$this->isDirectory();
    }

    /**
     * Check if a given event name exists in the event array.
     *
     * @param $event string
     *
     * @return bool
     */
    protected function eventExists($event)
    {
        return in_array($event, $this->events, true);
    }

    public function __toString()
    {
        return $this->input;
    }
}
