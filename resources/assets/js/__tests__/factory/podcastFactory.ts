import { Faker } from '@faker-js/faker'

export default (faker: Faker): Podcast => {
  return {
    type: 'podcasts',
    id: faker.datatype.uuid(),
    title: faker.lorem.sentence(),
    url: faker.internet.url(),
    link: faker.internet.url(),
    image: faker.image.imageUrl(),
    description: faker.lorem.paragraph(),
    author: faker.name.findName(),
    subscribed_at: faker.date.past().toISOString(),
    created_at: faker.date.past().toISOString(),
    state: {
      current_episode: null,
      progresses: {}
    }
  }
}
