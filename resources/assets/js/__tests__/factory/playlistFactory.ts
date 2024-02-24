import factory from 'factoria'
import { Faker } from '@faker-js/faker'

export default (faker: Faker): Playlist => ({
  type: 'playlists',
  user_id: faker.datatype.number({ min: 1, max: 1000 }),
  id: faker.datatype.uuid(),
  folder_id: faker.datatype.uuid(),
  name: faker.random.word(),
  is_smart: false,
  rules: [],
  own_songs_only: false,
  is_collaborative: false,
  cover: faker.image.imageUrl(),
})

export const states: Record<string, (faker: Faker) => Omit<Partial<Playlist>, 'type'>> = {
  smart: _ => ({
    is_smart: true,
    rules: [
      factory<SmartPlaylistRuleGroup>('smart-playlist-rule-group')
    ]
  }),
  orphan: _ => ({
    folder_id: null
  })
}
