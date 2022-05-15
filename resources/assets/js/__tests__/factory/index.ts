import factory from 'factoria'
import artistFactory from './artistFactory'
import albumFactory from './albumFactory'
import songFactory from './songFactory'
import playlistFactory from './playlistFactory'
import userFactory, { states as userStates } from './userFactory'
import youTubeVideoFactory from './youTubeVideoFactory'

factory
  .define('artist', faker => artistFactory(faker))
  .define('album', faker => albumFactory(faker))
  .define('song', faker => songFactory(faker))
  .define('video', faker => youTubeVideoFactory(faker))
  .define('playlist', faker => playlistFactory(faker))
  .define('user', faker => userFactory(faker), userStates)

export default factory
