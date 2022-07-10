import { Faker } from '@faker-js/faker'

export default (faker: Faker): ArtistInfo => ({
  image: faker.image.imageUrl(),
  bio: {
    summary: faker.lorem.sentence(),
    full: faker.lorem.sentences(4)
  },
  url: faker.internet.url()
})
