<?php

return [

    /*
    |----------------------------------------------------------------------
    | Auto backup mode
    |----------------------------------------------------------------------
    |
    | This value is used when you save your file content. If value is true,
    | the original file will be backed up before save.
    */

    'autoBackup' => true,

    /*
    |----------------------------------------------------------------------
    | Backup location
    |----------------------------------------------------------------------
    |
    | This value is used when you backup your file. This value is the sub
    | path from root folder of project application.
    */

    'backupPath' => base_path('storage/dotenv-editor/backups/'),

];
