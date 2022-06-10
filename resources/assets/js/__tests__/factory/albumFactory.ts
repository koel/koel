import factory from 'factoria'
import { Faker } from '@faker-js/faker'

export default (faker: Faker): Album => {
  const artist = factory<Artist>('artist')

  return {
    type: 'albums',
    artist_id: artist.id,
    artist_name: artist.name,
    song_count: 0,
    id: faker.datatype.number(),
    name: faker.lorem.sentence(),
    cover: faker.image.imageUrl(),
    info: {
      image: faker.image.imageUrl(),
      wiki: {
        summary: faker.lorem.sentence(),
        full: faker.lorem.paragraph()
      },
      tracks: [
        {
          title: faker.lorem.sentence(),
          length: 222,
          fmt_length: '3:42'
        },
        {
          title: faker.lorem.sentence(),
          length: 157,
          fmt_length: '2:37'
        }
      ],
      url: faker.internet.url()
    },
    play_count: 0,
    length: 0,
    fmt_length: '00:00:00'
  }
}
