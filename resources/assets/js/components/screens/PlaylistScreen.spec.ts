import { screen, waitFor } from '@testing-library/vue'
import type { Mock } from 'vite-plus/test'
import { beforeAll, describe, expect, it, vi } from 'vite-plus/test'
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

vi.mock('@/composables/useContextMenu')

describe('playlistScreen.vue', () => {
  const h = createHarness()

  beforeAll(async () => await import('@/components/playable/playable-list/PlayableList.vue'))

  const renderComponent = async (songs: Playable[] = [], playlist = h.factory('playlist').make()) => {
    h.actingAsUser(h.factory('user').state('current').make({ id: playlist.owner_id }) as CurrentUser)

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

    await waitFor(() => expect(fetchSongsMock).toHaveBeenCalledWith(playlist, playlist.is_smart))

    return {
      ...rendered,
      playlist,
      fetchSongsMock,
    }
  }

  it('fetches fresh songs every time a smart playlist is opened', async () => {
    const playlist = h.factory('playlist').state('smart').make()
    const { fetchSongsMock } = await renderComponent([], playlist)
    fetchSongsMock.mockResolvedValue([])

    h.visit('home')
    h.visit(`playlists/${playlist.id}`)

    await waitFor(() => expect(fetchSongsMock).toHaveBeenLastCalledWith(playlist, true))
    expect(fetchSongsMock).toHaveBeenCalledTimes(2)
  })

  it('says when a smart playlist shows only its first matches', async () => {
    await renderComponent(h.factory('song').make(500), h.factory('playlist').state('smart').make())

    screen.getByTestId('smart-playlist-limit-notice')
  })

  it('shows the songs of a playlist opened while another one is still loading', async () => {
    const first = h.factory('playlist').make()
    const second = h.factory('playlist').make({ owner_id: first.owner_id })
    h.actingAsUser(h.factory('user').state('current').make({ id: first.owner_id }) as CurrentUser)
    playlistStore.state.playlists = []
    playlistStore.init([first, second])
    first.playables = h.factory('song').make(3)
    second.playables = h.factory('song').make(2)

    let resolveFirst: ((playables: Playable[]) => void) | undefined

    h.mock(playableStore, 'fetchForPlaylist').mockImplementation(async playlist =>
      (playlist as Playlist).id === first.id
        ? new Promise<Playable[]>(resolve => (resolveFirst = resolve))
        : second.playables!,
    )

    h.render(Component, { global: { stubs: { FavoriteButton: h.stub('favorite-button', true) } } })
    h.visit(`playlists/${first.id}`)
    await waitFor(() => expect(resolveFirst).toBeDefined())

    h.visit(`playlists/${second.id}`)
    await waitFor(() => expect(screen.getAllByTestId('song-item')).toHaveLength(2))

    resolveFirst!(first.playables)
    await new Promise(resolve => setTimeout(resolve))

    expect(screen.getAllByTestId('song-item')).toHaveLength(2)
  })

  it('renders the playlist', async () => {
    await renderComponent(h.factory('song').make(10))

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
    fetchSongsMock.mockResolvedValue(h.factory('song').make(5))

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    expect(fetchSongsMock).toHaveBeenCalledWith(playlist, true)
  })

  it('shows Actions menu', async () => {
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
      fetchSongsMock.mockResolvedValueOnce(h.factory('song').make(5))

      eventBus.emit(eventKey, playlist)

      expect(fetchSongsMock).toHaveBeenCalledWith(playlist, false)
    },
  )
})
