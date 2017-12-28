import md5 from 'blueimp-md5'
import factory from 'factoria'
import crypto from 'crypto'

export default faker => {
  const artist = factory('artist')
  const album = factory('album', {
    artist,
    artist_id: artist.id
  })

  return {
    artist,
    album,
    artist_id: artist.id,
    album_id: album.id,
    id: crypto.createHash('md5'),
    title: faker.lorem.sentence(),
    length: faker.random.number(),
    track: faker.random.number(),
    disc: faker.random.number({ min: 1, max: 2 }),
    lyrics: faker.lorem.paragraph()
  }
}
