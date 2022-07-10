import factory from 'factoria'
import artistFactory, { states as artistStates } from './artistFactory'
import songFactory, { states as songStates } from '@/__tests__/factory/songFactory'
import albumFactory, { states as albumStates } from './albumFactory'
import playlistFactory, { states as playlistStates } from './playlistFactory'
import userFactory, { states as userStates } from './userFactory'
import albumTrackFactory from '@/__tests__/factory/albumTrackFactory'
import albumInfoFactory from '@/__tests__/factory/albumInfoFactory'
import artistInfoFactory from '@/__tests__/factory/artistInfoFactory'
import youTubeVideoFactory from './youTubeVideoFactory'

factory
  .define('artist', faker => artistFactory(faker), artistStates)
  .define('artist-info', faker => artistInfoFactory(faker))
  .define('album', faker => albumFactory(faker), albumStates)
  .define('album-track', faker => albumTrackFactory(faker))
  .define('album-info', faker => albumInfoFactory(faker))
  .define('song', faker => songFactory(faker), songStates)
  .define('video', faker => youTubeVideoFactory(faker))
  .define('playlist', faker => playlistFactory(faker), playlistStates)
  .define('user', faker => userFactory(faker), userStates)

export default factory
