import { describe, expect, it, vi } from 'vitest'
import { playlistStore } from '@/stores/playlistStore'
import { eventBus } from '@/utils/eventBus'
import { createHarness } from '@/__tests__/TestHarness'

vi.mock('@/composables/useErrorHandler', () => ({
  useErrorHandler: () => ({
    handleHttpError: vi.fn(),
  }),
}))

vi.mock('@/composables/useMessageToaster', () => ({
  useMessageToaster: () => ({
    toastSuccess: vi.fn(),
  }),
}))

import { usePlaylistContentManagement } from './usePlaylistContentManagement'

describe('usePlaylistContentManagement', () => {
  const h = createHarness()

  it('adds content to a playlist', async () => {
    const playlist = h.factory('playlist', { is_smart: false })
    const songs = [h.factory('song')]
    const emitMock = h.mock(eventBus, 'emit')
    h.mock(playlistStore, 'addContent').mockResolvedValue(undefined)

    const { addToPlaylist } = usePlaylistContentManagement()
    await addToPlaylist(playlist, songs)

    expect(playlistStore.addContent).toHaveBeenCalledWith(playlist, songs)
    expect(emitMock).toHaveBeenCalledWith('PLAYLIST_UPDATED', playlist)
  })

  it('does not add to smart playlists', async () => {
    const playlist = h.factory('playlist', { is_smart: true })
    const songs = [h.factory('song')]
    h.mock(playlistStore, 'addContent')

    const { addToPlaylist } = usePlaylistContentManagement()
    await addToPlaylist(playlist, songs)

    expect(playlistStore.addContent).not.toHaveBeenCalled()
  })

  it('removes content from a playlist', async () => {
    const playlist = h.factory('playlist', { is_smart: false })
    const songs = [h.factory('song')]
    const emitMock = h.mock(eventBus, 'emit')
    h.mock(playlistStore, 'removeContent').mockResolvedValue(undefined)

    const { removeFromPlaylist } = usePlaylistContentManagement()
    await removeFromPlaylist(playlist, songs)

    expect(playlistStore.removeContent).toHaveBeenCalledWith(playlist, songs)
    expect(emitMock).toHaveBeenCalledWith('PLAYLIST_CONTENT_REMOVED', playlist, songs)
  })
})
