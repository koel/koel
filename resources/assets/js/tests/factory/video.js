export default () => ({
  id: {
    videoId: faker.random.alphaNumeric()
  },
  snippet: {
    title: faker.lorem.sentence(),
    description: faker.lorem.paragraph(),
    thumbnails: {
      default: {
        url: faker.image.imageUrl()
      }
    }
  }
})
