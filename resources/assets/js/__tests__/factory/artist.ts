export default (faker: Faker.FakerStatic): Artist => ({
  id: faker.random.number(),
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
  albums: [],
  songs: [],
  playCount: 0,
  length: 0,
  fmtLength: '00:00:00'
})
