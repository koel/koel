<?php

namespace App\Enums;

enum AiSettingsSource: string
{
    case Organization = 'organization';
    case Environment = 'environment';
}
