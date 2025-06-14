import type { Faker } from '@faker-js/faker'

export default (faker: Faker): Artist => {
  return {
    type: 'artists',
    id: faker.string.ulid(),
    name: faker.person.fullName(),
    image: 'foo.jpg',
    created_at: faker.date.past().toISOString(),
  }
}

export const states: Record<string, Omit<Partial<Artist>, 'type'>> = {
  unknown: {
    name: 'Unknown Artist',
  },
  various: {
    name: 'Various Artists',
  },
}
