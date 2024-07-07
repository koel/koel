import { Faker } from '@faker-js/faker'

export default (faker: Faker): PlaylistCollaborator => ({
  type: 'playlist-collaborators',
  id: faker.datatype.number(),
  name: faker.name.findName(),
  avatar: 'https://gravatar.com/foo'
})
