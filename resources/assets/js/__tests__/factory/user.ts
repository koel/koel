import { Faker } from '@faker-js/faker'

export default (faker: Faker): User => ({
  id: faker.datatype.number(),
  name: faker.name.findName(),
  email: faker.internet.email(),
  password: faker.internet.password(),
  is_admin: false,
  avatar: 'https://gravatar.com/foo',
  preferences: {}
})
