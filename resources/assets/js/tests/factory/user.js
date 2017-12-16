export default () => ({
  id: faker.random.number(),
  name: faker.name.findName(),
  email: faker.internet.email(),
  is_admin: false
})
