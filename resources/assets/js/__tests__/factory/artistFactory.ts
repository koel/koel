import { faker } from '@faker-js/faker'

export default (): Artist => {
  return {
    type: 'artists',
    id: faker.string.ulid(),
    name: faker.person.fullName(),
    image: 'foo.jpg',
    created_at: faker.date.past().toISOString(),
    is_external: false,
    favorite: faker.datatype.boolean(),
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
