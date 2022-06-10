<?php

namespace App\Services;

use App\Exceptions\NonSmartPlaylistException;
use App\Factories\SmartPlaylistRuleParameterFactory;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Values\SmartPlaylistRule;
use App\Values\SmartPlaylistRuleGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SmartPlaylistService
{
    private const USER_REQUIRING_RULE_PREFIXES = ['interactions.'];

    private SmartPlaylistRuleParameterFactory $parameterFactory;

    public function __construct(SmartPlaylistRuleParameterFactory $parameterFactory)
    {
        $this->parameterFactory = $parameterFactory;
    }

    /** @return Collection|array<Song> */
    public function getSongs(Playlist $playlist): Collection
    {
        throw_unless($playlist->is_smart, NonSmartPlaylistException::create($playlist));

        $ruleGroups = $this->addRequiresUserRules($playlist->rule_groups, $playlist->user);

        return $this->buildQueryFromRules($ruleGroups, $playlist->user)
            ->orderBy('songs.title')
            ->get();
    }

    public function buildQueryFromRules(Collection $ruleGroups, User $user): Builder
    {
        $query = Song::withMeta($user);

        $ruleGroups->each(function (SmartPlaylistRuleGroup $group) use ($query): void {
            $query->orWhere(function (Builder $subQuery) use ($group): void {
                $group->rules->each(function (SmartPlaylistRule $rule) use ($subQuery): void {
                    $this->buildQueryForRule($subQuery, $rule);
                });
            });
        });

        return $query;
    }

    /**
     * Some rules need to be driven by an additional "user" factor, for example play count, liked, or last played
     * (basically everything related to interactions).
     * For those, we create an additional "user_id" rule.
     *
     * @return Collection|array<SmartPlaylistRuleGroup>
     */
    public function addRequiresUserRules(Collection $ruleGroups, User $user): Collection
    {
        return $ruleGroups->map(function (SmartPlaylistRuleGroup $group) use ($user): SmartPlaylistRuleGroup {
            $clonedGroup = clone $group;
            $additionalRules = collect();

            $group->rules->each(function (SmartPlaylistRule $rule) use ($additionalRules, $user): void {
                foreach (self::USER_REQUIRING_RULE_PREFIXES as $modelPrefix) {
                    if (starts_with($rule->model, $modelPrefix)) {
                        $additionalRules->add($this->createRequiresUserRule($user, $modelPrefix));
                    }
                }
            });

            // Make sure all those additional rules are unique.
            $clonedGroup->rules = $clonedGroup->rules->merge($additionalRules->unique('model')->collect());

            return $clonedGroup;
        });
    }

    private function createRequiresUserRule(User $user, string $modelPrefix): SmartPlaylistRule
    {
        return SmartPlaylistRule::create([
            'model' => $modelPrefix . 'user_id',
            'operator' => 'is',
            'value' => [$user->id],
        ]);
    }

    public function buildQueryForRule(Builder $query, SmartPlaylistRule $rule, ?string $model = null): Builder
    {
        if (!$model) {
            $model = $rule->model;
        }

        $fragments = explode('.', $model, 2);

        if (count($fragments) === 1) {
            return $query->{$this->resolveWhereLogic($rule)}(
                ...$this->parameterFactory->createParameters($model, $rule->operator, $rule->value)
            );
        }

        // If the model is something like 'artist.name' or 'interactions.play_count', we have a subquery to deal with.
        // We handle such a case with a recursive call which, in theory, should work with an unlimited level of nesting,
        // though in practice we only have one level max.
        return $query->whereHas(
            $fragments[0],
            fn (Builder $subQuery) => $this->buildQueryForRule($subQuery, $rule, $fragments[1])
        );
    }

    /**
     * Resolve the logic of a (sub)query base on the configured operator.
     * Basically, if the operator is "between," we use "whereBetween". Otherwise, it's "where". Simple.
     */
    private function resolveWhereLogic(SmartPlaylistRule $rule): string
    {
        return $rule->operator === SmartPlaylistRule::OPERATOR_IS_BETWEEN ? 'whereBetween' : 'where';
    }
}
