import factory from 'factoria'
import { faker } from '@faker-js/faker'

export default (): Playlist => ({
  type: 'playlists',
  owner_id: faker.string.ulid(),
  id: faker.string.uuid(),
  description: faker.lorem.sentence(),
  folder_id: faker.string.uuid(),
  name: faker.word.sample(),
  is_smart: false,
  rules: [],
  is_collaborative: false,
  cover: faker.image.url(),
})

export const states: Record<string, () => Omit<Partial<Playlist>, 'type'>> = {
  smart: () => ({
    is_smart: true,
    rules: [
      factory('smart-playlist-rule-group') as SmartPlaylistRuleGroup,
    ],
  }),
  orphan: () => ({
    folder_id: null,
  }),
}
