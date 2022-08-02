import factory from 'factoria'
import { Faker } from '@faker-js/faker'

export default (faker: Faker): Playlist => ({
  type: 'playlists',
  id: faker.datatype.number(),
  name: faker.random.word(),
  is_smart: false,
  rules: []
})

export const states: Record<string, () => Omit<Partial<Playlist>, 'type'>> = {
  smart: faker => ({
    is_smart: true,
    rules: [
      factory<SmartPlaylistRule>('smart-playlist-rule')
    ]
  })
}
