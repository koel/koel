import { Faker } from '@faker-js/faker'

export default (faker: Faker): Album => {
  const length = faker.datatype.number({ min: 300 })

  return {
    type: 'albums',
    artist_id: faker.datatype.number({ min: 3 }), // avoid Unknown and Various Artist by default
    artist_name: faker.name.findName(),
    song_count: faker.datatype.number(30),
    id: faker.datatype.number({ min: 2 }), // avoid Unknown Album by default
    name: faker.lorem.sentence(),
    cover: faker.image.imageUrl(),
    play_count: faker.datatype.number(),
    length,
    created_at: faker.date.past().toISOString()
  }
}

export const states: Record<string, Omit<Partial<Album>, 'type'>> = {
  unknown: {
    id: 1,
    name: 'Unknown Album',
    artist_id: 1,
    artist_name: 'Unknown Artist'
  }
}
