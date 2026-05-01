import factory from 'factoria'
import type { Factoria } from 'factoria'

declare module 'factoria' {
  namespace Factoria {
    interface ModelRegistry {
      album: Album
      'album-info': AlbumInfo
      'album-track': AlbumTrack
      artist: Artist
      'artist-info': ArtistInfo
      embed: Embed
      episode: Episode
      favorite: Favorite
      folder: Folder
      genre: Genre
      interaction: Interaction
      'live-event': LiveEvent
      playlist: Playlist
      'playlist-collaborator': PlaylistCollaborator
      'playlist-folder': PlaylistFolder
      podcast: Podcast
      'radio-station': RadioStation
      'smart-playlist-rule': SmartPlaylistRule
      'smart-playlist-rule-group': SmartPlaylistRuleGroup
      song: Song
      theme: Theme
      user: User
      'you-tube-video': YouTubeVideo
    }
  }
}

// Distributed pair: [model name, key of that model]. Lets `it.each` narrow the
// second tuple element to actual fields of the first.
export type ModelFieldPair = {
  [K in keyof Factoria.ModelRegistry]: [K, keyof Factoria.ModelRegistry[K] & string]
}[keyof Factoria.ModelRegistry]

// Dynamically import and register all factory modules
const factoryModules = import.meta.glob('@/__tests__/factory/*Factory.ts', { eager: true })

for (const [path, mod] of Object.entries(factoryModules)) {
  const match = path.match(/\/([^/]+)Factory\.ts$/)
  if (!match) {
    continue
  }

  const base = match[1]
  const modelName = base.replace(/[A-Z]/g, m => `-${m.toLowerCase()}`).replace(/^-/, '')

  const factoryFn = (mod as any).default
  const states = (mod as any).states

  factory.define(modelName, factoryFn, states)
}

export default factory
