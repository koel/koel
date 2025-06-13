import type { Faker } from '@faker-js/faker'

export default (faker: Faker): Episode => {
  return {
    type: 'episodes',
    id: faker.string.uuid(),
    title: faker.lorem.sentence(),
    length: faker.number.int(),
    created_at: faker.date.past().toISOString(),
    playback_state: 'Stopped',
    liked: false,
    play_count: 0,
    episode_link: faker.internet.url(),
    episode_description: faker.lorem.paragraph(),
    episode_image: faker.image.url(),
    podcast_id: faker.string.uuid(),
    podcast_title: faker.lorem.sentence(),
    podcast_author: faker.person.fullName(),
  }
}
