import factory from 'factoria'
import artist from './artist'
import album from './album'
import song from './song'
import video from './video'
import playlist from './playlist'
import user from './user'

factory
  .define('artist', (faker: Faker.FakerStatic): Artist => artist(faker))
  .define('album', (faker: Faker.FakerStatic): Album => album(faker))
  .define('song', (faker: Faker.FakerStatic): Song => song(faker))
  .define('video', (faker: Faker.FakerStatic): YouTubeVideo => video(faker))
  .define('playlist', (faker: Faker.FakerStatic): Playlist => playlist(faker))
  .define('user', (faker: Faker.FakerStatic): User => user(faker))

export default factory
