import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/QueuePlaybackService'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'

const togglePlayablesOfflineMock = vi.fn()

vi.mock('@/composables/useOfflinePlayback', () => ({
  useOfflinePlayback: () => ({
    swReady: ref(true),
    allPlayablesCached: () => false,
    hasOfflineCapablePlayables: (playables: Playable[]) => playables.length > 0,
    togglePlayablesOffline: togglePlayablesOfflineMock,
  }),
}))

import Component from './SelectedAlbumsContextMenu.vue'

describe('selectedAlbumsContextMenu.vue', () => {
  const h = createHarness({
    beforeEach: () => togglePlayablesOfflineMock.mockClear(),
  })

  const renderComponent = async (albums = h.factory('album').make({ favorite: false }, 3)) => {
    const songs = h.factory('song').make(5)
    const fetchMock = h.mock(playableStore, 'fetchSongsForAlbums').mockResolvedValue(songs)

    h.render(Component, { props: { albums } })
    await h.tick()

    return { albums, songs, fetchMock }
  }

  it.each([
    ['Play All', false],
    ['Shuffle All', true],
  ])('%s', async (label, shuffled) => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'queueAndPlay')
    const { albums, songs, fetchMock } = await renderComponent()

    await h.user.click(screen.getByText(label))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(albums)
    expect(playMock).toHaveBeenCalledWith(songs, shuffled)
  })

  it('favorites when any album is not a favorite', async () => {
    const favoriteMock = h.mock(albumStore, 'favorite')
    const albums = h.factory('album').make({ favorite: true }, 3)
    albums[1].favorite = false
    await renderComponent(albums)

    await h.user.click(screen.getByText('Favorite'))

    expect(favoriteMock).toHaveBeenCalledWith(albums)
  })

  it('undoes favorite when all albums are favorites', async () => {
    const undoFavoriteMock = h.mock(albumStore, 'undoFavorite')
    const { albums } = await renderComponent(h.factory('album').make({ favorite: true }, 3))

    await h.user.click(screen.getByText('Undo Favorite'))

    expect(undoFavoriteMock).toHaveBeenCalledWith(albums)
  })

  it('downloads only standard albums', async () => {
    const downloadMock = h.mock(downloadService, 'fromAlbums')
    const standardAlbums = h.factory('album').make(2)
    await renderComponent([...standardAlbums, h.factory('album').state('unknown').make()])

    await h.user.click(screen.getByText('Download'))

    expect(downloadMock).toHaveBeenCalledWith(standardAlbums)
  })

  it('does not have an option to download if downloading is disabled', async () => {
    commonStore.state.allows_download = false
    await renderComponent()

    expect(screen.queryByText('Download')).toBeNull()
  })

  it('makes the songs of all albums available offline', async () => {
    const { songs } = await renderComponent()

    await h.user.click(screen.getByText('Make Available Offline'))

    expect(togglePlayablesOfflineMock).toHaveBeenCalledWith(songs, undefined)
  })
})
