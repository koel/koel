import factory from 'factoria'

export default (faker: Faker.FakerStatic): Album => {
  const artist = factory<Artist>('artist')

  return {
    artist,
    id: faker.random.number(),
    artist_id: artist.id,
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
          fmtLength: '3:42'
        },
        {
          title: faker.lorem.sentence(),
          length: 157,
          fmtLength: '2:37'
        }
      ],
      url: faker.internet.url()
    },
    songs: [],
    is_compilation: false,
    playCount: 0,
    length: 0,
    fmtLength: '00:00:00'
  }
}
