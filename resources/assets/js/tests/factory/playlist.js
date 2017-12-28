import factory from 'factoria'

export default faker => ({
  id: faker.random.number(),
  name: faker.random.word(),
  songs: factory('song', 10)
})
