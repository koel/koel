import { faker } from '@faker-js/faker'

export default (): PlaylistCollaborator => ({
  type: 'playlist-collaborators',
  id: faker.string.ulid(),
  name: faker.person.fullName(),
  avatar: 'https://gravatar.com/foo',
})
