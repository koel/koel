import { Faker } from '@faker-js/faker'

export default (faker: Faker): SmartPlaylistRule => ({
  id: faker.datatype.number(),
  model: faker.helpers.arrayElement<SmartPlaylistModel['name']>(['title', 'artist.name', 'album.name']),
  operator: faker.helpers.arrayElement<SmartPlaylistOperator['operator']>(['is', 'contains', 'isNot']),
  value: [faker.random.word()]
})
