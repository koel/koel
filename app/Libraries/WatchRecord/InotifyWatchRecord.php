<?php

namespace App\Libraries\WatchRecord;

class InotifyWatchRecord extends WatchRecord implements WatchRecordInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(string $input)
    {
        parent::__construct($input);

        $this->parse($input);
    }

    /**
     * Parse the inotifywait's output. The inotifywait command should be something like:
     * $ inotifywait -rme move,close_write,delete --format "%e %w%f" $MEDIA_PATH.
     */
    public function parse(string $string): void
    {
        list($events, $this->path) = explode(' ', $string, 2);
        $this->events = explode(',', $events);
    }

    /**
     * Determine if the object has just been deleted or moved from our watched directory.
     */
    public function isDeleted(): bool
    {
        return $this->eventExists('DELETE') || $this->eventExists('MOVED_FROM');
    }

    /**
     * Determine if the object has just been created or modified.
     * For our purpose, we watch both the CREATE and the CLOSE_WRITE event, because some operating
     * systems only support CREATE, but not CLOSE_WRITE and MOVED_TO.
     * Additionally, a MOVED_TO (occurred after the object has been moved/renamed to another location
     * **under our watched directory**) should be considered as "modified" also.
     */
    public function isNewOrModified(): bool
    {
        return $this->eventExists('CLOSE_WRITE') || $this->eventExists('CREATE') || $this->eventExists('MOVED_TO');
    }

    public function isDirectory(): bool
    {
        return $this->eventExists('ISDIR');
    }
}
