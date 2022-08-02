<?php

namespace App\Rules;

use App\Values\SmartPlaylistRule;
use Illuminate\Contracts\Validation\Rule;
use Throwable;

class ValidSmartPlaylistRulePayload implements Rule
{
    /** @param array $value */
    public function passes($attribute, $value): bool
    {
        try {
            foreach ((array) $value as $ruleGroupConfig) {
                foreach ($ruleGroupConfig['rules'] as $rule) {
                    SmartPlaylistRule::assertConfig($rule, false);
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function message(): string
    {
        return 'Invalid smart playlist rules';
    }
}
