import { screen, waitFor } from '@testing-library/vue'
import type { Mock } from 'vitest'
import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { playlistStore } from '@/stores/playlistStore'
import { playableStore } from '@/stores/playableStore'
import Router from '@/router'
import type { Events } from '@/config/events'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import PlaylistContextMenu from '@/components/playlist/PlaylistContextMenu.vue'
import Component from './PlaylistScreen.vue'

describe('playlistScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async (songs: Playable[] = []) => {
    const playlist = h.factory('playlist')
    h.actingAsUser(h.factory.states('current')('user', { id: playlist.owner_id }) as CurrentUser)

    playlistStore.state.playlists = []
    playlistStore.init([playlist])
    playlist.playables = songs

    const fetchSongsMock = h.mock(playableStore, 'fetchForPlaylist').mockResolvedValueOnce(songs)

    const rendered = h.render(Component, {
      global: {
        stubs: {
          FavoriteButton: h.stub('favorite-button', true),
        },
      },
    })

    h.visit(`playlists/${playlist.id}`)

    await waitFor(() => expect(fetchSongsMock).toHaveBeenCalledWith(playlist, false))

    return {
      ...rendered,
      playlist,
      fetchSongsMock,
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

  it('refreshes the playlist', async () => {
    const { playlist, fetchSongsMock } = await renderComponent()
    fetchSongsMock.mockResolvedValue(h.factory('song', 5))

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    expect(fetchSongsMock).toHaveBeenCalledWith(playlist, true)
  })

  it('shows Actions menu', async () => {
    vi.mock('@/composables/useContextMenu')
    const { openContextMenu } = useContextMenu()
    const { playlist } = await renderComponent()

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'More Actions' }))
      await assertOpenContextMenu(openContextMenu as Mock, PlaylistContextMenu, { playlist })
    })
  })

  it('goes back to home if playlist is deleted', async () => {
    const goMock = h.mock(Router, 'go')
    const { playlist } = await renderComponent()
    eventBus.emit('PLAYLIST_DELETED', playlist)

    await h.tick()

    expect(goMock).toHaveBeenCalledWith('/#/home')
  })

  it.each<[keyof Events]>([['PLAYLIST_UPDATED'], ['PLAYLIST_COLLABORATOR_REMOVED']])(
    'refreshes upon %s event trigger',
    async eventKey => {
      const { playlist, fetchSongsMock } = await renderComponent()
      fetchSongsMock.mockResolvedValueOnce(h.factory('song', 5))

      eventBus.emit(eventKey, playlist)

      expect(fetchSongsMock).toHaveBeenCalledWith(playlist, false)
    },
  )
})
