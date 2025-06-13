import factory from 'factoria'
import type { Faker } from '@faker-js/faker'

export default (faker: Faker): Playlist => ({
  type: 'playlists',
  owner_id: faker.string.ulid(),
  id: faker.string.uuid(),
  folder_id: faker.string.uuid(),
  name: faker.word.sample(),
  is_smart: false,
  rules: [],
  own_songs_only: false,
  is_collaborative: false,
  cover: faker.image.url(),
})

export const states: Record<string, (faker: Faker) => Omit<Partial<Playlist>, 'type'>> = {
  smart: _ => ({
    is_smart: true,
    rules: [
      factory('smart-playlist-rule-group'),
    ],
  }),
  orphan: _ => ({
    folder_id: null,
  }),
}
