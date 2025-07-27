import { faker } from '@faker-js/faker'

export default (): Podcast => {
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
    favorite: faker.datatype.boolean(),
    state: {
      current_episode: null,
      progresses: {},
    },
  }
}
