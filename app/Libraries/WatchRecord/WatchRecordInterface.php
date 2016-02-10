<?php

namespace App\Libraries\WatchRecord;

interface WatchRecordInterface
{
    public function parse($string);

    public function getPath();

    public function isDeleted();

    public function isNewOrModified();

    public function isDirectory();

    public function isFile();
}
