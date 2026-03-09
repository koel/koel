import { reactive, type Reactive } from 'vue'
import { http } from '@/services/http'
import { playlistStore } from '@/stores/playlistStore'
import { radioStationStore } from '@/stores/radioStationStore'

type AiHandledResult = {
  message: string
} & (
  | { action: 'create_smart_playlist'; resource: Reactive<Playlist> }
  | { action: 'add_radio_station'; resource: Reactive<RadioStation> }
  | { action: 'play_radio_station'; resource: Reactive<RadioStation> }
  | { action: 'play_songs'; resource: Song[] }
  | { action: null; resource: undefined }
)

export const aiService = {
  conversationId: null as string | null,

  prompt: async (text: string, context?: { songId?: string, radioStationId?: string }) => {
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
      return { message, action: 'play_songs', resource: response.data.songs }
    }

    return { message, action: null, resource: undefined }
  },
}
