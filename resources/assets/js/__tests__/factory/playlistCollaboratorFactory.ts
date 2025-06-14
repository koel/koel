import type { Faker } from '@faker-js/faker'

export default (faker: Faker): PlaylistCollaborator => ({
  type: 'playlist-collaborators',
  id: faker.string.ulid(),
  name: faker.person.fullName(),
  avatar: 'https://gravatar.com/foo',
})
