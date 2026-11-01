import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/QueuePlaybackService'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import Component from './SelectedArtistsContextMenu.vue'

describe('selectedArtistsContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = (artists = h.factory('artist').make({ favorite: false }, 3)) => {
    h.render(Component, { props: { artists } })

    return { artists }
  }

  it.each([
    ['Play All', false],
    ['Shuffle All', true],
  ])('%s', async (label, shuffled) => {
    h.createAudioPlayer()

    const songs = h.factory('song').make(5)
    const fetchMock = h.mock(playableStore, 'fetchSongsForArtists').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')
    const { artists } = renderComponent()

    await h.user.click(screen.getByText(label))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(artists)
    expect(playMock).toHaveBeenCalledWith(songs, shuffled)
  })

  it('favorites when any artist is not a favorite', async () => {
    const favoriteMock = h.mock(artistStore, 'favorite')
    const artists = h.factory('artist').make({ favorite: true }, 3)
    artists[1].favorite = false
    renderComponent(artists)

    await h.user.click(screen.getByText('Favorite'))

    expect(favoriteMock).toHaveBeenCalledWith(artists)
  })

  it('undoes favorite when all artists are favorites', async () => {
    const undoFavoriteMock = h.mock(artistStore, 'undoFavorite')
    const { artists } = renderComponent(h.factory('artist').make({ favorite: true }, 3))

    await h.user.click(screen.getByText('Undo Favorite'))

    expect(undoFavoriteMock).toHaveBeenCalledWith(artists)
  })

  it('downloads only standard artists', async () => {
    const downloadMock = h.mock(downloadService, 'fromArtists')
    const standardArtists = h.factory('artist').make(2)
    renderComponent([...standardArtists, h.factory('artist').state('unknown').make()])

    await h.user.click(screen.getByText('Download'))

    expect(downloadMock).toHaveBeenCalledWith(standardArtists)
  })

  it('does not have an option to download if no standard artist is selected', () => {
    renderComponent([h.factory('artist').state('unknown').make(), h.factory('artist').state('various').make()])

    expect(screen.queryByText('Download')).toBeNull()
  })

  it('does not have an option to download if downloading is disabled', () => {
    commonStore.state.allows_download = false
    renderComponent()

    expect(screen.queryByText('Download')).toBeNull()
  })
})
