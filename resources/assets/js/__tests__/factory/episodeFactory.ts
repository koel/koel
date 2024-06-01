import { Faker } from '@faker-js/faker'

export default (faker: Faker): Episode => {
  return {
    type: 'episodes',
    id: faker.datatype.uuid(),
    title: faker.lorem.sentence(),
    length: faker.datatype.number(),
    created_at: faker.date.past().toISOString(),
    playback_state: 'Stopped',
    liked: false,
    play_count: 0,
    episode_link: faker.internet.url(),
    episode_description: faker.lorem.paragraph(),
    episode_image: faker.image.imageUrl(),
    podcast_id: faker.datatype.uuid(),
    podcast_title: faker.lorem.sentence(),
    podcast_author: faker.name.findName()
  }
}
