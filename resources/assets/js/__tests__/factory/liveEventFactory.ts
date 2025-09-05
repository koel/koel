import { faker } from '@faker-js/faker'

export default (): LiveEvent => {
  return {
    type: 'live-events',
    id: faker.string.ulid(),
    name: faker.word.words(),
    dates: {
      start: faker.date.future().toISOString(),
      end: faker.date.future().toISOString(),
    },
    url: faker.internet.url(),
    image: faker.image.url(),
    venue: {
      name: faker.word.words(),
      url: faker.internet.url(),
      city: faker.location.city(),
    },
  }
}
