<?php

namespace App\Services;

use App\Models\Playlist;
use App\Models\Rule;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class SmartPlaylistService
{
    private const RULE_REQUIRES_USER_PREFIXES = ['interactions.'];

    private $songRepository;

    public function __construct(SongRepository $songRepository)
    {
        $this->songRepository = $songRepository;
    }

    public function getSongs(Playlist $playlist): Collection
    {
        if (!$playlist->is_smart) {
            throw new RuntimeException($playlist->name.' is not a smart playlist.');
        }

        $rules = $this->addRequiresUserRules($playlist->rules, $playlist->user);

        return $this->buildQueryForRules($rules)->get();
    }

    public function buildQueryForRules(array $rules): Builder
    {
        return tap(Song::query(), static function (Builder $query) use ($rules): Builder {
            foreach ($rules as $config) {
                $query = Rule::create($config)->build($query);
            }

            return $query;
        });
    }

    /**
     * Some rules need to be driven by an additional "user" factor, for example play count, liked, or last played
     * (basically everything related to interactions).
     * For those, we create an additional "user_id" rule.
     *
     * @param string[] $rules
     */
    private function addRequiresUserRules(array $rules, User $user): array
    {
        $additionalRules = [];

        foreach ($rules as $rule) {
            foreach (self::RULE_REQUIRES_USER_PREFIXES as $modelPrefix) {
                if (starts_with($rule['model'], $modelPrefix)) {
                    $additionalRules[] = $this->createRequireUserRule($user, $modelPrefix);
                }
            }
        }

        // make sure all those additional rules are unique.
        return array_merge($rules, collect($additionalRules)->unique('model')->all());
    }

    private function createRequireUserRule(User $user, string $modelPrefix): array
    {
        return [
            'logic' => 'and',
            'model' => $modelPrefix.'user_id',
            'operator' => 'is',
            'value' => [$user->id],
        ];
    }
}
