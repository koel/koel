import { Faker } from '@faker-js/faker'
import factory from 'factoria'
import artist from './artist'
import album from './album'
import song from './song'
import video from './video'
import playlist from './playlist'
import user from './user'

factory
  .define('artist', (faker: Faker): Artist => artist(faker))
  .define('album', (faker: Faker): Album => album(faker))
  .define('song', (faker: Faker): Song => song(faker))
  .define('video', (faker: Faker): YouTubeVideo => video(faker))
  .define('playlist', (faker: Faker): Playlist => playlist(faker))
  .define('user', (faker: Faker): User => user(faker))

export default factory
