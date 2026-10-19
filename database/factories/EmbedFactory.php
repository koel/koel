<?php

namespace Database\Factories;

use App\Enums\EmbeddableType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Embed;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Embed> */
class EmbedFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        /** @var EmbeddableType $type */
        $type = fake()->randomElement(EmbeddableType::cases());

        return [
            'user_id' => User::factory(),
            'embeddable_type' => $type->value,
            'embeddable_id' => static fn (array $attributes) => self::createEmbeddableOwnedBy(
                $type,
                User::query()->whereKey($attributes['user_id'])->firstOrFail(),
            )
                ->getKey(),
        ];
    }

    private static function createEmbeddableOwnedBy(EmbeddableType $type, User $user): Song|Playlist|Album|Artist
    {
        return match ($type) {
            EmbeddableType::PLAYABLE => Song::factory()->for($user, 'owner')->createOne(),
            EmbeddableType::PLAYLIST => tap(Playlist::factory()->createOne(), static function (Playlist $playlist) use (
                $user,
            ): void {
                $playlist->users()->detach();
                $playlist->users()->attach($user, ['role' => 'owner']);
            }),
            EmbeddableType::ALBUM => Album::factory()->for($user)->createOne(),
            EmbeddableType::ARTIST => Artist::factory()->for($user)->createOne(),
        };
    }
}
