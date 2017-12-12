import factory from '.'

export default () => {
  const artist = factory('artist')

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
        { title: faker.lorem.sentence(), fmtLength: '3:42' },
        { title: faker.lorem.sentence(), fmtLength: '2:37' },
      ],
      url: faker.internet.url()
    },
    songs: []
  }
}
