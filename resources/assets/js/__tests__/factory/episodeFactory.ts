import { faker } from '@faker-js/faker'

export default (): Episode => {
  const title = faker.lorem.sentence()

  return {
    type: 'episodes',
    id: faker.string.uuid(),
    title,
    title_sort: title,
    length: faker.number.int(),
    created_at: faker.date.past().toISOString(),
    playback_state: 'Stopped',
    favorite: faker.datatype.boolean(),
    play_count: 0,
    rating: 0,
    episode_link: faker.internet.url(),
    episode_description: faker.lorem.paragraph(),
    episode_image: faker.image.url(),
    podcast_id: faker.string.uuid(),
    podcast_title: faker.lorem.sentence(),
    podcast_author: faker.person.fullName(),
  }
}
