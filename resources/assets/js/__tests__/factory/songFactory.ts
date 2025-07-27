import { faker } from '@faker-js/faker'
import { genres } from '@/config/genres'

const generate = (partOfCompilation = false): Song => {
  const artistId = faker.string.ulid()
  const artistName = faker.person.fullName()

  return {
    type: 'songs',
    owner_id: faker.string.uuid(),
    artist_id: artistId,
    album_id: faker.string.ulid(),
    artist_name: artistName,
    album_name: faker.lorem.sentence(),
    album_artist_id: partOfCompilation ? faker.string.ulid() + 1 : artistId,
    album_artist_name: partOfCompilation ? artistName : faker.person.fullName(),
    album_cover: faker.image.url(),
    id: faker.string.uuid(),
    title: faker.lorem.sentence(),
    length: faker.number.int(),
    track: faker.number.int({ min: 1, max: 20 }),
    disc: faker.number.int({ min: 1, max: 2 }),
    genre: faker.helpers.arrayElement(genres),
    year: faker.helpers.arrayElement([null, faker.date.past().getFullYear()]),
    lyrics: faker.lorem.paragraph(),
    play_count: faker.number.int(),
    favorite: faker.datatype.boolean(),
    is_public: faker.datatype.boolean(),
    created_at: faker.date.past().toISOString(),
    playback_state: 'Stopped',
    is_external: false,
  }
}

export default (): Song => generate()

export const states: Record<string, Partial<Song>> = {
  partOfCompilation: generate(true),
}
