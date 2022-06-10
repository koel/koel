import { Faker } from '@faker-js/faker'

export default (faker: Faker): Artist => ({
  type: 'artists',
  id: faker.datatype.number(),
  name: faker.name.findName(),
  info: {
    image: faker.image.imageUrl(),
    bio: {
      summary: faker.lorem.sentence(),
      full: faker.lorem.paragraph()
    },
    url: faker.internet.url()
  },
  image: 'foo.jpg',
  play_count: 0,
  album_count: 0,
  song_count: 0,
  length: 0,
  fmt_length: '00:00:00'
})
