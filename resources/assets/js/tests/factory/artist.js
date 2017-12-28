export default faker => ({
  id: faker.random.number(),
  name: faker.name.findName(),
  info: {
    image: faker.image.imageUrl(),
    bio: {
      summary: faker.lorem.sentence(),
      full: faker.lorem.paragraph()
    },
    url: faker.internet.url()
  }
})
