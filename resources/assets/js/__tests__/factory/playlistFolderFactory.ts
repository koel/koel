import { faker } from '@faker-js/faker'

export default (): PlaylistFolder => ({
  type: 'playlist-folders',
  id: faker.string.uuid(),
  name: faker.word.sample(),
})
