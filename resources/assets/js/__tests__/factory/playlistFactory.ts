import factory from 'factoria'
import { Faker } from '@faker-js/faker'

export default (faker: Faker): Playlist => ({
  type: 'playlists',
  id: faker.datatype.number(),
  folder_id: faker.datatype.uuid(),
  name: faker.random.word(),
  is_smart: false,
  rules: [],
  ownSongsOnly: false
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
