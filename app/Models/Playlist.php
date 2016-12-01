<?php

namespace App\Models;

use App\Traits\CanFilterByUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int   user_id
 * @property Collection songs
 */
class Playlist extends Model
{
    use CanFilterByUser;

    protected $hidden = ['user_id', 'created_at', 'updated_at'];

    protected $guarded = ['id'];

    protected $casts = [
        'user_id' => 'int',
    ];

    public function songs()
    {
        return $this->belongsToMany(Song::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
