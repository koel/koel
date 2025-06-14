import type { Faker } from '@faker-js/faker'

export default (faker: Faker): Interaction => ({
  type: 'interactions',
  id: faker.number.int({ min: 1 }),
  song_id: faker.string.uuid(),
  liked: faker.datatype.boolean(),
  play_count: faker.number.int({ min: 1 }),
})
