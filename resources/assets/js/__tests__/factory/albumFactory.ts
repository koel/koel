import { faker } from '@faker-js/faker'

export default (): Album => {
  return {
    type: 'albums',
    artist_id: faker.string.ulid(),
    artist_name: faker.person.fullName(),
    id: faker.string.ulid(),
    name: faker.lorem.sentence(),
    cover: faker.image.url(),
    created_at: faker.date.past().toISOString(),
    year: faker.date.past().getFullYear(),
    is_external: false,
    favorite: faker.datatype.boolean(),
  }
}

export const states: Record<string, Omit<Partial<Album>, 'type'>> = {
  unknown: {
    name: 'Unknown Album',
    artist_name: 'Unknown Artist',
  },
}
