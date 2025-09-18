import { faker } from '@faker-js/faker'

export default (): Embed => {
  return {
    type: 'embeds',
    id: faker.string.ulid(),
    user_id: faker.string.uuid(),
    embeddable_id: faker.string.ulid(),
    embeddable_type: faker.helpers.arrayElement<Embed['embeddable_type']>(['playable', 'playlist', 'artist', 'album']),
  }
}
