import type { Faker } from '@faker-js/faker'

export default (faker: Faker): Podcast => {
  return {
    type: 'podcasts',
    id: faker.string.uuid(),
    title: faker.lorem.sentence(),
    url: faker.internet.url(),
    link: faker.internet.url(),
    image: faker.image.url(),
    description: faker.lorem.paragraph(),
    author: faker.person.fullName(),
    subscribed_at: faker.date.past().toISOString(),
    last_played_at: faker.date.past().toISOString(),
    state: {
      current_episode: null,
      progresses: {},
    },
  }
}
