<?php

namespace App\Enums\Acl;

enum Permission: string
{
    case MANAGE_SETTINGS = 'manage settings'; // media path, plus edition, SSO, etc.
    case MANAGE_USERS = 'manage users'; // create, edit, delete users
    case MANAGE_SONGS = 'manage songs'; // upload, edit, delete
    case MANAGE_RADIO_STATIONS = 'manage radio stations'; // create, edit, delete
    case MANAGE_PODCASTS = 'manage podcasts'; // create, edit, delete podcasts
}
