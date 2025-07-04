<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

/**
 * @property string $id
 * @property string $path
 * @property Collection<array-key, Song> $songs
 * @property ?Folder $parent
 * @property Collection<array-key, Folder> $subfolders
 * @property-read string $name
 * @property-read bool $is_uploads_folder Where the folder is the uploads folder by any user
 * @property-read ?int $uploader_id
 * @property ?string $parent_id
 * @property string $hash
 */
class Folder extends Model
{
    use HasFactory;
    use HasUuids;

    protected $guarded = [];
    public $timestamps = false;
    protected $appends = ['name'];

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class)->orderBy('path');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__);
    }

    public function subfolders(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id')->orderBy('path');
    }

    public function browsableBy(User $user): bool
    {
        return !$this->is_uploads_folder || $this->uploader_id === $user->id;
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => Arr::last(explode(DIRECTORY_SEPARATOR, $this->path)));
    }

    protected function isUploadsFolder(): Attribute
    {
        // An uploads folder has a format of __KOEL_UPLOADS_$<id>__ and is a child of the root folder
        // (i.e., it has no parent).
        return Attribute::get(
            fn () => !$this->parent_id && preg_match('/^__KOEL_UPLOADS_\$\d+__$/', $this->name) === 1
        );
    }

    protected function uploaderId(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->is_uploads_folder) {
                return null;
            }

            $matches = [];
            preg_match('/^__KOEL_UPLOADS_\$(\d+)__$/', $this->name, $matches);

            return (int) Arr::get($matches, 1);
        })->shouldCache();
    }
}
