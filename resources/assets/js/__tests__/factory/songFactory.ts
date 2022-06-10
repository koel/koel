import factory from 'factoria'
import crypto from 'crypto-random-string'
import { Faker } from '@faker-js/faker'

export default (faker: Faker): Song => {
  const artist = factory<Artist>('artist')
  const album = factory<Album>('album', {
    artist_id: artist.id
  })

  return {
    type: 'songs',
    artist_id: artist.id,
    album_id: album.id,
    artist_name: artist.name,
    album_name: album.name,
    album_artist_id: album.artist_id,
    album_artist_name: album.artist_name,
    album_cover: album.cover,
    id: crypto(32),
    title: faker.lorem.sentence(),
    length: faker.datatype.number(),
    track: faker.datatype.number(),
    disc: faker.datatype.number({ min: 1, max: 2 }),
    lyrics: faker.lorem.paragraph(),
    play_count: 0,
    liked: true
  }
}
