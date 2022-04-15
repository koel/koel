import factory from 'factoria'

export default (faker: Faker.FakerStatic): Playlist => ({
  id: faker.random.number(),
  name: faker.random.word(),
  songs: factory<Song>('song', 10),
  is_smart: false,
  rules: []
})
