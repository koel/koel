import factory from '.'

export default () => ({
  id: faker.random.number(),
  name: faker.random.word(),
  songs: factory('song', 10)
})
