import { Faker } from '@faker-js/faker'

export default (faker: Faker): Interaction => ({
  type: 'interactions',
  id: faker.datatype.number({ min: 1 }),
  song_id: faker.datatype.uuid(),
  liked: faker.datatype.boolean(),
  play_count: faker.datatype.number({ min: 1 })
})
