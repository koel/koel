import type { Factoria } from 'factoria'
import factoria from 'factoria'
import albumFactory, { states as albumStates } from '@/__tests__/factory/albumFactory'
import albumInfoFactory from '@/__tests__/factory/albumInfoFactory'
import albumTrackFactory from '@/__tests__/factory/albumTrackFactory'
import artistFactory, { states as artistStates } from '@/__tests__/factory/artistFactory'
import artistInfoFactory from '@/__tests__/factory/artistInfoFactory'
import episodeFactory from '@/__tests__/factory/episodeFactory'
import favoriteFactory from '@/__tests__/factory/favoriteFactory'
import folderFactory from '@/__tests__/factory/folderFactory'
import genreFactory from '@/__tests__/factory/genreFactory'
import interactionFactory from '@/__tests__/factory/interactionFactory'
import liveEventFactory from '@/__tests__/factory/liveEventFactory'
import playlistCollaboratorFactory from '@/__tests__/factory/playlistCollaboratorFactory'
import playlistFactory, { states as playlistStates } from '@/__tests__/factory/playlistFactory'
import playlistFolderFactory from '@/__tests__/factory/playlistFolderFactory'
import podcastFactory from '@/__tests__/factory/podcastFactory'
import radioStationFactory from '@/__tests__/factory/radioStationFactory'
import smartPlaylistRuleFactory from '@/__tests__/factory/smartPlaylistRuleFactory'
import smartPlaylistRuleGroupFactory from '@/__tests__/factory/smartPlaylistRuleGroupFactory'
import songFactory, { states as songStates } from '@/__tests__/factory/songFactory'
import userFactory, { states as userStates } from '@/__tests__/factory/userFactory'
import youTubeVideoFactory from '@/__tests__/factory/youTubeVideoFactory'

interface ModelToTypeMap {
  'album': Album
  'album-info': AlbumInfo
  'album-track': AlbumTrack
  'artist': Artist
  'artist-info': ArtistInfo
  'episode': Episode
  'favorite': Favorite
  'folder': Folder
  'genre': Genre
  'interaction': Interaction
  'live-event': LiveEvent
  'playlist': Playlist
  'playlist-collaborator': PlaylistCollaborator
  'playlist-folder': PlaylistFolder
  'podcast': Podcast
  'radio-station': RadioStation
  'smart-playlist-rule': SmartPlaylistRule
  'smart-playlist-rule-group': SmartPlaylistRuleGroup
  'song': Song
  'user': User
  'video': YouTubeVideo
}

type Model = keyof ModelToTypeMap
type Overrides<M extends Model> = Factoria.Overrides<ModelToTypeMap[M]>

const define: typeof factoria.define = (model, handle, states) =>
  factoria.define(model, handle, states)

function factory<M extends Model> (
  model: M,
  overrides?: Overrides<M>,
): ModelToTypeMap[M]

function factory<M extends Model> (
  model: M,
  count: 1,
  overrides?: Overrides<M>,
): ModelToTypeMap[M]

function factory<M extends Model> (
  model: M,
  count: number,
  overrides?: Overrides<M>,
): ModelToTypeMap[M][]

function factory<M extends Model> (
  model: M,
  count: number | Overrides<M> = 1,
  overrides?: Overrides<M>,
) {
  return typeof count === 'number'
    ? count === 1 ? factoria(model, overrides) : factoria(model, count, overrides)
    : factoria(model, count)
}

const states = (...states: string[]): typeof factory => {
  factoria.states(...states)
  return factory
}

factory.states = states

export default factory as typeof factory & {
  states: typeof states
}

define('album', albumFactory, albumStates)
define('album-info', albumInfoFactory)
define('album-track', albumTrackFactory)
define('artist', artistFactory, artistStates)
define('artist-info', artistInfoFactory)
define('episode', episodeFactory)
define('favorite', favoriteFactory)
define('folder', folderFactory)
define('genre', genreFactory)
define('interaction', interactionFactory)
define('live-event', liveEventFactory)
define('playlist', playlistFactory, playlistStates)
define('playlist-collaborator', playlistCollaboratorFactory)
define('playlist-folder', playlistFolderFactory)
define('podcast', podcastFactory)
define('radio-station', radioStationFactory)
define('smart-playlist-rule', smartPlaylistRuleFactory)
define('smart-playlist-rule-group', smartPlaylistRuleGroupFactory)
define('song', songFactory, songStates)
define('user', userFactory, userStates)
define('video', youTubeVideoFactory)
