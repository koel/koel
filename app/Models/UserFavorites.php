<?php

namespace App\Models;

use App\Models\Concerns\MorphsToEmbeds;
use App\Models\Contracts\Embeddable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * A user's list of favorites, keyed by the user's public ID.
 *
 * @property-read string $public_id
 * @property-read string $name
 */
#[Table(name: 'users', key: 'public_id', keyType: 'string', incrementing: false)]
class UserFavorites extends Model implements Embeddable
{
    use MorphsToEmbeds;
}
