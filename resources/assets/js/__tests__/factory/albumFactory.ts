import type { Faker } from '@faker-js/faker'

export default (faker: Faker): Album => {
  return {
    type: 'albums',
    artist_id: faker.string.ulid(),
    artist_name: faker.person.fullName(),
    id: faker.string.ulid(),
    name: faker.lorem.sentence(),
    cover: faker.image.url(),
    created_at: faker.date.past().toISOString(),
    year: faker.date.past().getFullYear(),
  }
}

export const states: Record<string, Omit<Partial<Album>, 'type'>> = {
  unknown: {
    name: 'Unknown Album',
    artist_name: 'Unknown Artist',
  },
}
