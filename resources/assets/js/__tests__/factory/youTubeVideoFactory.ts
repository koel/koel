import { Faker } from '@faker-js/faker'

export default (faker: Faker): YouTubeVideo => ({
  id: {
    videoId: faker.random.alphaNumeric(16)
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
