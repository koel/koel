import factory from 'factoria'
import artistFactory, { states as artistStates } from './artistFactory'
import albumFactory, { states as albumStates } from './albumFactory'
import songFactory, { states as songStates } from '@/__tests__/factory/songFactory'
import playlistFactory, { states as playlistStates } from './playlistFactory'
import userFactory, { states as userStates } from './userFactory'
import youTubeVideoFactory from './youTubeVideoFactory'

factory
  .define('artist', faker => artistFactory(faker), artistStates)
  .define('album', faker => albumFactory(faker), albumStates)
  .define('song', faker => songFactory(faker), songStates)
  .define('video', faker => youTubeVideoFactory(faker))
  .define('playlist', faker => playlistFactory(faker), playlistStates)
  .define('user', faker => userFactory(faker), userStates)

export default factory
