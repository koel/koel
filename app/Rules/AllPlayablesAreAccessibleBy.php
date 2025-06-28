<?php

namespace App\Rules;

use App\Models\User;
use App\Repositories\SongRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class AllPlayablesAreAccessibleBy implements ValidationRule
{
    public function __construct(private readonly User $user)
    {
    }

    /** @param array<string> $value */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ids = array_unique(Arr::wrap($value));

        if ($ids && $this->countAccessiblePlayables($ids) !== count($ids)) {
            $fail('Not all songs or episodes exist in the database or are accessible by the user.');
        }
    }

    private function countAccessiblePlayables(array $ids): int
    {
        return app(SongRepository::class)->countAccessibleByIds($ids, $this->user);
    }
}
