import { Faker } from '@faker-js/faker'

export default (faker: Faker): User => ({
  type: 'users',
  id: faker.datatype.number(),
  name: faker.name.findName(),
  email: faker.internet.email(),
  password: faker.internet.password(),
  is_prospect: false,
  is_admin: false,
  avatar: 'https://gravatar.com/foo',
  preferences: undefined
})

export const states: Record<string, Omit<Partial<User>, 'type'>> = {
  admin: {
    is_admin: true
  },
  prospect: {
    is_prospect: true
  }
}
