<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Enums\Acl\Role;
use App\Models\User;

final class UserResource
{
    public const array JSON_STRUCTURE = [
        'username',
        'email',
        'scrobblingEnabled',
        'adminRole',
        'settingsRole',
        'downloadRole',
        'uploadRole',
        'playlistRole',
        'coverArtRole',
        'commentRole',
        'podcastRole',
        'streamRole',
        'jukeboxRole',
        'shareRole',
    ];

    /**
     * @return array{
     *     username: string,
     *     email: string,
     *     scrobblingEnabled: bool,
     *     adminRole: bool,
     *     settingsRole: bool,
     *     downloadRole: bool,
     *     uploadRole: bool,
     *     playlistRole: bool,
     *     coverArtRole: bool,
     *     commentRole: bool,
     *     podcastRole: bool,
     *     streamRole: bool,
     *     jukeboxRole: bool,
     *     shareRole: bool,
     * }
     */
    public static function toArray(User $user): array
    {
        $isAdmin = $user->role === Role::ADMIN;
        $canManage = $isAdmin || $user->role === Role::MANAGER;

        return [
            'username' => $user->email,
            'email' => $user->email,
            'scrobblingEnabled' => true,
            'adminRole' => $isAdmin,
            'settingsRole' => true,
            'downloadRole' => true,
            'uploadRole' => $canManage,
            'playlistRole' => true,
            'coverArtRole' => $canManage,
            'commentRole' => false,
            'podcastRole' => true,
            'streamRole' => true,
            'jukeboxRole' => false,
            'shareRole' => false,
        ];
    }
}
