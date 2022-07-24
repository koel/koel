import crypto from 'crypto-random-string'
import { Faker } from '@faker-js/faker'

export default (faker: Faker): Interaction => ({
  type: 'interactions',
  id: faker.datatype.number({ min: 1 }),
  song_id: crypto(32),
  liked: faker.datatype.boolean(),
  play_count: faker.datatype.number({ min: 1 })
})
