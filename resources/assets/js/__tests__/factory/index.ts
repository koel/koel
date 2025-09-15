import type { Factoria } from 'factoria'
import factoria from 'factoria'

export interface ModelToTypeMap {
  'album': Album
  'album-info': AlbumInfo
  'album-track': AlbumTrack
  'artist': Artist
  'artist-info': ArtistInfo
  'embed': Embed
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
  'you-tube-video': YouTubeVideo
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

// Dynamically import and register all factory modules
const factoryModules = import.meta.glob('@/__tests__/factory/*Factory.ts', { eager: true })

for (const [path, mod] of Object.entries(factoryModules)) {
  const match = path.match(/\/([^/]+)Factory\.ts$/)
  if (!match) {
    continue
  }

  const base = match[1]
  const modelName = base
    .replace(/[A-Z]/g, m => `-${m.toLowerCase()}`)
    .replace(/^-/, '')

  const factoryFn = (mod as any).default
  const states = (mod as any).states

  define(modelName, factoryFn, states)
}
