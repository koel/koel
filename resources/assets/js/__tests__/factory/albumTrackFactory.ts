import type { Faker } from '@faker-js/faker'

export default (faker: Faker): AlbumTrack => ({
  title: faker.lorem.sentence(),
  length: faker.number.int({ min: 180, max: 1_800 }),
})
