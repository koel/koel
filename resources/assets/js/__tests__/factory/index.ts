import factory from 'factoria'
import artistFactory, { states as artistStates } from '@/__tests__/factory/artistFactory'
import songFactory, { states as songStates } from '@/__tests__/factory/songFactory'
import albumFactory, { states as albumStates } from '@/__tests__/factory/albumFactory'
import interactionFactory from '@/__tests__/factory/interactionFactory'
import smartPlaylistRuleFactory from '@/__tests__/factory/smartPlaylistRuleFactory'
import smartPlaylistRuleGroupFactory from '@/__tests__/factory/smartPlaylistRuleGroupFactory'
import playlistFactory, { states as playlistStates } from '@/__tests__/factory/playlistFactory'
import playlistFolderFactory from '@/__tests__/factory/playlistFolderFactory'
import userFactory, { states as userStates } from '@/__tests__/factory/userFactory'
import albumTrackFactory from '@/__tests__/factory/albumTrackFactory'
import albumInfoFactory from '@/__tests__/factory/albumInfoFactory'
import artistInfoFactory from '@/__tests__/factory/artistInfoFactory'
import youTubeVideoFactory from '@/__tests__/factory/youTubeVideoFactory'
import genreFactory from '@/__tests__/factory/genreFactory'

export default factory
  .define('artist', faker => artistFactory(faker), artistStates)
  .define('artist-info', faker => artistInfoFactory(faker))
  .define('album', faker => albumFactory(faker), albumStates)
  .define('album-track', faker => albumTrackFactory(faker))
  .define('album-info', faker => albumInfoFactory(faker))
  .define('song', faker => songFactory(faker), songStates)
  .define('interaction', faker => interactionFactory(faker))
  .define('genre', faker => genreFactory(faker))
  .define('video', faker => youTubeVideoFactory(faker))
  .define('smart-playlist-rule', faker => smartPlaylistRuleFactory(faker))
  .define('smart-playlist-rule-group', faker => smartPlaylistRuleGroupFactory(faker))
  .define('playlist', faker => playlistFactory(faker), playlistStates)
  .define('playlist-folder', faker => playlistFolderFactory(faker))
  .define('user', faker => userFactory(faker), userStates)
