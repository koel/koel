import { faker } from '@faker-js/faker'

export default (): Favorite => {
  return {
    type: 'favorites',
    favoriteable_id: faker.string.uuid(),
    favoriteable_type: faker.helpers.arrayElement(['podcast', 'playable', 'album', 'artist']),
    user_id: faker.string.uuid(),
    created_at: faker.date.past().toISOString(),
  }
}
