import factory from 'factoria'
import crypto from 'crypto-random-string'

export default (faker: Faker.FakerStatic): Song => {
  const artist = factory<Artist>('artist')
  const album = factory<Album>('album', {
    artist,
    artist_id: artist.id
  })

  return {
    artist,
    album,
    artist_id: artist.id,
    album_id: album.id,
    id: crypto(32),
    title: faker.lorem.sentence(),
    length: faker.random.number(),
    track: faker.random.number(),
    disc: faker.random.number({ min: 1, max: 2 }),
    lyrics: faker.lorem.paragraph(),
    playCount: 0,
    liked: true
  }
}
