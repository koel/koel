<?php

namespace App\Hooks;

enum Action: string
{
    case APPLICATION_BOOTED = 'application-booted';
}
