import type { Faker } from '@faker-js/faker'
import factory from 'factoria'

export default (faker: Faker): AlbumInfo => ({
  cover: faker.image.imageUrl(),
  wiki: {
    summary: faker.lorem.sentence(),
    full: faker.lorem.sentences(4),
  },
  tracks: factory('album-track', 8),
  url: faker.internet.url(),
})
