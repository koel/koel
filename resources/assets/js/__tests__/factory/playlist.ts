import factory from 'factoria'
import { Faker } from '@faker-js/faker'

export default (faker: Faker): Playlist => ({
  id: faker.datatype.number(),
  name: faker.random.word(),
  songs: factory<Song>('song', 10),
  is_smart: false,
  rules: []
})
