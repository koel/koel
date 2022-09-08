import { Faker } from '@faker-js/faker'

export default (faker: Faker): SmartPlaylistRule => ({
  id: faker.datatype.number(),
  model: faker.random.arrayElement<SmartPlaylistModel['name']>(['title', 'artist.name', 'album.name']),
  operator: faker.random.arrayElement<SmartPlaylistOperator['operator']>(['is', 'contains', 'isNot']),
  value: [faker.random.word()]
})
