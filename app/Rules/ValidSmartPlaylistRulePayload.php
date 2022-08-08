<?php

namespace App\Rules;

use App\Values\SmartPlaylistRule;
use Illuminate\Contracts\Validation\Rule;

class ValidSmartPlaylistRulePayload implements Rule
{
    public function passes($attribute, $value): bool
    {
        return attempt(static function () use ($value) {
            foreach ((array) $value as $ruleGroupConfig) {
                foreach ($ruleGroupConfig['rules'] as $rule) {
                    SmartPlaylistRule::assertConfig($rule, false);
                }
            }

            return true;
        }, false) ?? false;
    }

    public function message(): string
    {
        return 'Invalid smart playlist rules';
    }
}
