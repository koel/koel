import factory from 'factoria'
import { faker } from '@faker-js/faker'

export default (): AlbumInfo => ({
  cover: faker.image.url(),
  wiki: {
    summary: faker.lorem.sentence(),
    full: faker.lorem.sentences(4),
  },
  tracks: factory('album-track', 8) as unknown as AlbumTrack[],
  url: faker.internet.url(),
})
