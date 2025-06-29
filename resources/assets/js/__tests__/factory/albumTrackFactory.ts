import { faker } from '@faker-js/faker'

export default (): AlbumTrack => ({
  title: faker.lorem.sentence(),
  length: faker.number.int({ min: 180, max: 1_800 }),
})
