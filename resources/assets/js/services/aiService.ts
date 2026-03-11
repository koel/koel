import { differenceBy } from 'lodash'
import { reactive, type Reactive } from 'vue'
import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'
import { playlistStore } from '@/stores/playlistStore'
import { podcastStore } from '@/stores/podcastStore'
import { radioStationStore } from '@/stores/radioStationStore'

type AiHandledResult = {
  message: string
} & (
  | { action: 'create_smart_playlist'; resource: Reactive<Playlist> }
  | { action: 'add_radio_station'; resource: Reactive<RadioStation> }
  | { action: 'play_radio_station'; resource: Reactive<RadioStation> }
  | { action: 'play_songs'; resource: Song[]; queue: boolean }
  | { action: 'suggest_songs'; resource: undefined }
  | { action: 'add_to_favorites'; resource: undefined }
  | { action: 'remove_from_favorites'; resource: undefined }
  | { action: 'add_to_playlist'; resource: { songs: Song[]; playlist: Reactive<Playlist> } }
  | { action: 'remove_from_playlist'; resource: { songs: Song[]; playlist: Reactive<Playlist> } }
  | { action: 'update_album'; resource: undefined }
  | { action: 'update_artist'; resource: undefined }
  | { action: 'show_lyrics'; resource: undefined }
  | { action: 'update_lyrics'; resource: undefined }
  | { action: null; resource: undefined }
)

export const aiService = {
  conversationId: null as string | null,

  prompt: async (text: string, context?: { songId?: string; radioStationId?: string }) => {
    return await http.post<AiResponse>('ai/prompt', {
      prompt: text,
      current_song_id: context?.songId,
      current_radio_station_id: context?.radioStationId,
      conversation_id: aiService.conversationId,
    })
  },

  handleResponse: (response: AiResponse): AiHandledResult => {
    const { message } = response
    aiService.conversationId = response.conversation_id

    if (response.action === 'create_smart_playlist' && response.data.playlist) {
      const playlist = reactive(response.data.playlist)
      playlistStore.setupSmartPlaylist(playlist)
      playlistStore.state.playlists.push(playlist)
      return { message, action: 'create_smart_playlist', resource: playlist }
    }

    if (response.action === 'add_radio_station' && response.data.station) {
      return { message, action: 'add_radio_station', resource: radioStationStore.sync(response.data.station)[0] }
    }

    if (response.action === 'play_radio_station' && response.data.station) {
      return { message, action: 'play_radio_station', resource: radioStationStore.sync(response.data.station)[0] }
    }

    if (response.action === 'play_songs' && response.data.songs) {
      const count = response.data.songs.length
      const fallback = response.data.queue
        ? `Added ${count} song${count === 1 ? '' : 's'} to the queue.`
        : `Playing ${count} song${count === 1 ? '' : 's'}.`

      return {
        message: message || fallback,
        action: 'play_songs',
        resource: response.data.songs,
        queue: response.data.queue ?? false,
      }
    }

    if (response.action === 'suggest_songs' && response.data.songs) {
      playableStore.syncWithVault(response.data.songs)
      const list = response.data.list ? `\n\n${response.data.list}` : ''
      return { message: `${message}${list}`, action: 'suggest_songs', resource: undefined }
    }

    if (response.action === 'add_to_favorites') {
      aiService.syncFavoriteState(response.data, true)
      return { message, action: 'add_to_favorites', resource: undefined }
    }

    if (response.action === 'remove_from_favorites') {
      aiService.syncFavoriteState(response.data, false)
      return { message, action: 'remove_from_favorites', resource: undefined }
    }

    if (response.action === 'add_to_playlist' && response.data.songs && response.data.playlist) {
      playableStore.syncWithVault(response.data.songs)
      cache.remove(['playlist.songs', response.data.playlist.id])
      return {
        message,
        action: 'add_to_playlist',
        resource: { songs: response.data.songs, playlist: reactive(response.data.playlist) },
      }
    }

    if (response.action === 'remove_from_playlist' && response.data.songs && response.data.playlist) {
      cache.remove(['playlist.songs', response.data.playlist.id])
      return {
        message,
        action: 'remove_from_playlist',
        resource: { songs: response.data.songs, playlist: reactive(response.data.playlist) },
      }
    }

    if (response.action === 'update_album' && response.data.album) {
      albumStore.syncWithVault(response.data.album)
      return { message, action: 'update_album', resource: undefined }
    }

    if (response.action === 'update_artist' && response.data.artist) {
      artistStore.syncWithVault(response.data.artist)
      return { message, action: 'update_artist', resource: undefined }
    }

    if (response.action === 'show_lyrics' && response.data.lyrics) {
      return {
        message: response.data.lyrics,
        action: 'show_lyrics',
        resource: undefined,
      }
    }

    if (response.action === 'update_lyrics' && response.data.lyrics) {
      if (response.data.song) {
        playableStore.syncWithVault(response.data.song)
      }

      return {
        message: response.data.lyrics,
        action: 'update_lyrics',
        resource: undefined,
      }
    }

    return { message, action: null, resource: undefined }
  },

  resetConversation: () => {
    aiService.conversationId = null
  },

  syncFavoriteState: (data: AiResponse['data'], favorite: boolean) => {
    const type = data.type ?? 'playable'

    if (type === 'playable' && data.songs) {
      const songs = playableStore.syncWithVault(data.songs)
      songs.forEach(song => (song.favorite = favorite))

      if (favorite) {
        playableStore.state.favorites.push(...songs)
      } else {
        playableStore.state.favorites = differenceBy(playableStore.state.favorites, songs, 'id')
      }
    } else if (type === 'album' && data.albums) {
      data.albums.forEach(album => {
        const local = albumStore.byId(album.id)
        if (local) {
          local.favorite = favorite
        }
      })
    } else if (type === 'artist' && data.artists) {
      data.artists.forEach(artist => {
        const local = artistStore.byId(artist.id)
        if (local) {
          local.favorite = favorite
        }
      })
    } else if (type === 'radio-station' && data.stations) {
      data.stations.forEach(station => {
        const local = radioStationStore.byId(station.id)
        if (local) {
          local.favorite = favorite
        }
      })
    } else if (type === 'podcast' && data.podcasts) {
      data.podcasts.forEach(podcast => {
        const local = podcastStore.byId(podcast.id)
        if (local) {
          local.favorite = favorite
        }
      })
    }
  },
}
