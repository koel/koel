export default (faker: Faker.FakerStatic): User => ({
  id: faker.random.number(),
  name: faker.name.findName(),
  email: faker.internet.email(),
  password: faker.internet.password(),
  is_admin: false,
  avatar: 'https://gravatar.com/foo',
  preferences: {}
})
