import type { Faker } from '@faker-js/faker'

export default (faker: Faker): YouTubeVideo => ({
  id: {
    videoId: faker.string.alphanumeric(16),
  },
  snippet: {
    title: faker.lorem.sentence(),
    description: faker.lorem.paragraph(),
    thumbnails: {
      default: {
        url: faker.image.url(),
      },
    },
  },
})
