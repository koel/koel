<?php

namespace App\Libraries\WatchRecord;

class InotifyWatchRecord extends WatchRecord implements WatchRecordInterface
{
    /**
     * InotifyWatchRecord constructor.
     * {@inheritdoc}
     *
     * @param $string
     */
    public function __construct($string)
    {
        parent::__construct($string);
        $this->parse($string);
    }

    /**
     * Parse the inotifywait's output. The inotifywait command should be something like:
     * $ inotifywait -rme move,close_write,delete --format "%e %w%f" $MEDIA_PATH.
     *
     * @param $string string The output string.
     */
    public function parse($string)
    {
        list($events, $this->path) = explode(' ', $string, 2);
        $this->events = explode(',', $events);
    }

    /**
     * Determine if the object has just been deleted or moved from our watched directory.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->eventExists('DELETE') || $this->eventExists('MOVED_FROM');
    }

    /**
     * Determine if the object has just been created or modified.
     * For our purpose, we watch both the CREATE and the CLOSE_WRITE event, because some operating
     * systems only support CREATE, but not CLOSE_WRITE and MOVED_TO.
     * Additionally, a MOVED_TO (occurred after the object has been moved/renamed to another location
     * **under our watched directory**) should be considered as "modified" also.
     *
     * @return bool
     */
    public function isNewOrModified()
    {
        return $this->eventExists('CLOSE_WRITE') || $this->eventExists('CREATE') || $this->eventExists('MOVED_TO');
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory()
    {
        return $this->eventExists('ISDIR');
    }
}
