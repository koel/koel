<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
