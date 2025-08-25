import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { playlistStore } from '@/stores/playlistStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import Component from './PlaylistScreen.vue'

describe('playlistScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async (songs: Playable[] = []) => {
    const playlist = h.factory('playlist')
    h.be(h.factory('user', { id: playlist.owner_id }))

    playlistStore.state.playlists = []
    playlistStore.init([playlist])
    playlist.playables = songs

    const fetchMock = h.mock(playableStore, 'fetchForPlaylist').mockResolvedValue(songs)

    const rendered = h.render(Component)

    await h.router.activateRoute({
      path: `playlists/${playlist.id}`,
      screen: 'Playlist',
    }, { id: playlist.id })

    await waitFor(() => expect(fetchMock).toHaveBeenCalledWith(playlist, false))

    return {
      ...rendered,
      playlist,
      fetchMock,
    }
  }

  it('renders the playlist', async () => {
    await renderComponent(h.factory('song', 10))

    await waitFor(() => {
      screen.getByTestId('song-list')
      expect(screen.queryByTestId('screen-empty-state')).toBeNull()
    })
  })

  it('displays the empty state if playlist is empty', async () => {
    await renderComponent()

    await waitFor(() => {
      screen.getByTestId('screen-empty-state')
      expect(screen.queryByTestId('song-list')).toBeNull()
    })
  })

  it('downloads the playlist', async () => {
    const downloadMock = h.mock(downloadService, 'fromPlaylist')
    const { playlist } = await renderComponent(h.factory('song', 10))

    await h.tick(2)
    await h.user.click(screen.getByRole('button', { name: 'Download All' }))

    await waitFor(() => expect(downloadMock).toHaveBeenCalledWith(playlist))
  })

  it('deletes the playlist', async () => {
    const emitMock = h.mock(eventBus, 'emit')
    const { playlist } = await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Delete this playlist' }))

    await waitFor(() => expect(emitMock).toHaveBeenCalledWith('PLAYLIST_DELETE', playlist))
  })

  it('refreshes the playlist', async () => {
    const { playlist, fetchMock } = await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    expect(fetchMock).toHaveBeenCalledWith(playlist, true)
  })
})
