import { Faker } from '@faker-js/faker'
import { genres } from '@/config'

export default (faker: Faker): Genre => {
  return {
    type: 'genres',
    name: faker.helpers.arrayElement(genres),
    song_count: faker.datatype.number({ min: 1, max: 1_000 }),
    length: faker.datatype.number({ min: 300, max: 300_000 })
  }
}
