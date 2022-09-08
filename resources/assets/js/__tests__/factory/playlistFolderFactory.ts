import { Faker } from '@faker-js/faker'

export default (faker: Faker): PlaylistFolder => ({
  type: 'playlist-folders',
  id: faker.datatype.uuid(),
  name: faker.random.word()
})
