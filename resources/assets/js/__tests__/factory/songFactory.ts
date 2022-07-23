import crypto from 'crypto-random-string'
import { Faker, faker } from '@faker-js/faker'

const generate = (partOfCompilation = false): Song => {
  const artistId = faker.datatype.number({ min: 3 })
  const artistName = faker.name.findName()

  return {
    type: 'songs',
    artist_id: artistId,
    album_id: faker.datatype.number({ min: 2 }), // avoid Unknown Album by default
    artist_name: artistName,
    album_name: faker.lorem.sentence(),
    album_artist_id: partOfCompilation ? artistId + 1 : artistId,
    album_artist_name: partOfCompilation ? artistName : faker.name.findName(),
    album_cover: faker.image.imageUrl(),
    id: crypto(32),
    title: faker.lorem.sentence(),
    length: faker.datatype.number(),
    track: faker.datatype.number(),
    disc: faker.datatype.number({ min: 1, max: 2 }),
    lyrics: faker.lorem.paragraph(),
    play_count: faker.datatype.number(),
    liked: faker.datatype.boolean(),
    created_at: faker.date.past().toISOString(),
    playback_state: 'Stopped'
  }
}

export default (faker: Faker): Song => {
  return generate()
}

export const states: Record<string, Partial<Song>> = {
  partOfCompilation: generate(true)
}
