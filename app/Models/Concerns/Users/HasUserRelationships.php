<?php

namespace App\Models\Concerns\Users;

use App\Models\Interaction;
use App\Models\Organization;
use App\Models\Playlist;
use App\Models\PlaylistFolder;
use App\Models\Podcast;
use App\Models\PodcastUserPivot;
use App\Models\RadioStation;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasUserRelationships
{
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class)->withPivot('role', 'position')->withTimestamps();
    }

    public function ownedPlaylists(): BelongsToMany
    {
        return $this->playlists()->wherePivot('role', 'owner');
    }

    public function collaboratedPlaylists(): BelongsToMany
    {
        return $this->playlists()->wherePivot('role', 'collaborator');
    }

    public function playlistFolders(): HasMany
    {
        return $this->hasMany(PlaylistFolder::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function podcasts(): BelongsToMany
    {
        return $this->belongsToMany(Podcast::class)->using(PodcastUserPivot::class)->withTimestamps();
    }

    public function radioStations(): HasMany
    {
        return $this->hasMany(RadioStation::class);
    }

    public function themes(): HasMany
    {
        return $this->hasMany(Theme::class);
    }
}
