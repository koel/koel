<?php

namespace App\Repositories;

use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Repository<Theme>
 */
class ThemeRepository extends Repository
{
    public function getAllByUser(User $user): Collection
    {
        return $user->themes->sortByDesc('created_at');
    }

    public function findUserThemeById(string $id, User $user): ?Theme
    {
        return Theme::query()->whereBelongsTo($user)->find($id);
    }
}
