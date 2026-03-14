import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { playlistStore } from '@/stores/playlistStore'
import { playableStore } from '@/stores/playableStore'
import { radioStationStore } from '@/stores/radioStationStore'
import { aiService } from './aiService'

describe('aiService', () => {
  const h = createHarness()

  describe('prompt', () => {
    it('sends a prompt to the API', async () => {
      const response: AiResponse = {
        message: 'Done.',
        action: null,
        conversation_id: 'conv-1',
        data: {},
      }

      h.mock(http, 'post').mockResolvedValue(response)

      const result = await aiService.prompt('Play jazz', { songId: 'song-1' })

      expect(http.post).toHaveBeenCalledWith('ai/prompt', {
        prompt: 'Play jazz',
        current_song_id: 'song-1',
        current_radio_station_id: undefined,
        conversation_id: null,
      })
      expect(result).toEqual(response)
    })

    it('includes conversation ID in subsequent requests', async () => {
      aiService.conversationId = 'conv-existing'
      h.mock(http, 'post').mockResolvedValue({ message: '', action: null, conversation_id: 'conv-existing', data: {} })

      await aiService.prompt('Next')

      expect(http.post).toHaveBeenCalledWith(
        'ai/prompt',
        expect.objectContaining({
          conversation_id: 'conv-existing',
        }),
      )

      aiService.conversationId = null
    })
  })

  describe('handleResponse', () => {
    it('handles play_songs action', () => {
      const songs = h.factory('song', 3)
      const response: AiResponse = {
        message: 'Playing 3 songs.',
        action: 'play_songs',
        conversation_id: 'conv-1',
        data: { songs, queue: false },
      }

      const result = aiService.handleResponse(response)

      expect(result.action).toBe('play_songs')
      expect(result.message).toBe('Playing 3 songs.')

      if (result.action === 'play_songs') {
        expect(result.resource).toEqual(songs)
        expect(result.queue).toBe(false)
      }
    })

    it('handles play_songs with queue flag', () => {
      const songs = h.factory('song', 2)
      const response: AiResponse = {
        message: 'Added to queue.',
        action: 'play_songs',
        conversation_id: null,
        data: { songs, queue: true },
      }

      const result = aiService.handleResponse(response)

      if (result.action === 'play_songs') {
        expect(result.queue).toBe(true)
      }
    })

    it('handles create_smart_playlist action', () => {
      const playlist = h.factory('playlist')
      h.mock(playlistStore, 'setupSmartPlaylist')

      const response: AiResponse = {
        message: 'Playlist created.',
        action: 'create_smart_playlist',
        conversation_id: null,
        data: { playlist },
      }

      const result = aiService.handleResponse(response)

      expect(result.action).toBe('create_smart_playlist')
      expect(playlistStore.setupSmartPlaylist).toHaveBeenCalled()
    })

    it('handles add_radio_station action', () => {
      const station = h.factory('radio-station')
      h.mock(radioStationStore, 'sync').mockReturnValue([station])

      const response: AiResponse = {
        message: 'Station added.',
        action: 'add_radio_station',
        conversation_id: null,
        data: { station },
      }

      const result = aiService.handleResponse(response)

      expect(result.action).toBe('add_radio_station')
      expect(radioStationStore.sync).toHaveBeenCalledWith(station)
    })

    it('handles add_to_favorites action', () => {
      const songs = h.factory('song', 2)
      h.mock(playableStore, 'syncWithVault').mockReturnValue(songs)

      const response: AiResponse = {
        message: 'Added to favorites.',
        action: 'add_to_favorites',
        conversation_id: null,
        data: { type: 'playable', songs },
      }

      const result = aiService.handleResponse(response)

      expect(result.action).toBe('add_to_favorites')
    })

    it('handles show_lyrics action', () => {
      const response: AiResponse = {
        message: 'Here are the lyrics:',
        action: 'show_lyrics',
        conversation_id: null,
        data: { lyrics: 'Is this the real life\nIs this just fantasy' },
      }

      const result = aiService.handleResponse(response)

      expect(result.action).toBe('show_lyrics')
      expect(result.message).toContain('Is this the real life')
    })

    it('handles null action', () => {
      const response: AiResponse = {
        message: 'I cannot do that.',
        action: null,
        conversation_id: null,
        data: {},
      }

      const result = aiService.handleResponse(response)

      expect(result.action).toBeNull()
      expect(result.message).toBe('I cannot do that.')
    })

    it('updates conversation ID from response', () => {
      aiService.conversationId = null

      aiService.handleResponse({
        message: 'Done.',
        action: null,
        conversation_id: 'new-conv',
        data: {},
      })

      expect(aiService.conversationId).toBe('new-conv')
      aiService.conversationId = null
    })
  })
})
