<?php

namespace App\Models;

use Database\Factories\PasskeyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Passkeys\Passkey as BasePasskey;

/**
 * @property int $user_id
 *
 * @method static PasskeyFactory factory(...$parameters)
 */
class Passkey extends BasePasskey
{
    use HasFactory;
}
