import factoria, { Factoria } from 'factoria'
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
import playlistCollaboratorFactory from '@/__tests__/factory/playlistCollaboratorFactory'
import episodeFactory from '@/__tests__/factory/episodeFactory'
import podcastFactory from '@/__tests__/factory/podcastFactory'
import { Faker } from '@faker-js/faker'

type ModelToTypeMap = {
  artist: Artist
  'artist-info': ArtistInfo
  album: Album
  'album-track': AlbumTrack
  'album-info': AlbumInfo
  song: Song
  interaction: Interaction
  genre: Genre
  video: YouTubeVideo
  'smart-playlist-rule': SmartPlaylistRule
  'smart-playlist-rule-group': SmartPlaylistRuleGroup
  playlist: Playlist
  'playlist-folder': PlaylistFolder
  user: User
  'playlist-collaborator': PlaylistCollaborator
  episode: Episode
  podcast: Podcast
}

type Model = keyof ModelToTypeMap
type Overrides<M extends Model> = Factoria.Overrides<ModelToTypeMap[M]>

const define = <M extends Model>(
  model: M,
  handle: (faker: Faker) => Overrides<M>,
  states?: Record<string, Factoria.StateDefinition>
) => factoria.define(model, handle, states)

function factory <M extends Model>(
  model: M,
  overrides?: Overrides<M>
): ModelToTypeMap[M]

function factory <M extends Model>(
  model: M,
  count: 1,
  overrides?: Overrides<M>
): ModelToTypeMap[M]

function factory <M extends Model>(
  model: M,
  count: number,
  overrides?: Overrides<M>
): ModelToTypeMap[M][]

function factory <M extends Model>(
  model: M,
  count: number|Overrides<M> = 1,
  overrides?: Overrides<M>
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

define('artist', artistFactory, artistStates)
define('artist-info', artistInfoFactory)
define('album', albumFactory, albumStates)
define('album-track', albumTrackFactory)
define('album-info', albumInfoFactory)
define('song', songFactory, songStates)
define('interaction', interactionFactory)
define('genre', genreFactory)
define('video', youTubeVideoFactory)
define('smart-playlist-rule', smartPlaylistRuleFactory)
define('smart-playlist-rule-group', smartPlaylistRuleGroupFactory)
define('playlist', playlistFactory, playlistStates)
define('playlist-folder', playlistFolderFactory)
define('user', userFactory, userStates)
define('playlist-collaborator', playlistCollaboratorFactory)
define('episode', episodeFactory)
define('podcast', podcastFactory)
