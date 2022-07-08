import factory from 'factoria'
import { Faker } from '@faker-js/faker'

export default (faker: Faker): Playlist => ({
  type: 'playlists',
  id: faker.datatype.number(),
  name: faker.random.word(),
  songs: factory<Song[]>('song', 10),
  is_smart: false,
  rules: []
})

export const states: Record<string, Omit<Partial<Playlist>, 'type'>> = {
  smart: {
    is_smart: true
  }
}
