import { faker } from '@faker-js/faker'
import { genres } from '@/config/genres'

export default (): Genre => {
  return {
    type: 'genres',
    id: faker.string.ulid(),
    name: faker.helpers.arrayElement(genres),
    song_count: faker.number.int({ min: 1, max: 1_000 }),
    length: faker.number.int({ min: 300, max: 300_000 }),
  }
}
