import { screen } from '@testing-library/vue'
import type { Mock } from 'vitest'
import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/QueuePlaybackService'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { albumStore } from '@/stores/albumStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import AlbumContextMenu from './AlbumContextMenu.vue'
import Component from './AlbumCard.vue'

describe('albumCard', () => {
  const h = createHarness()

  const createAlbum = (overrides: Partial<Album> = {}) => {
    return h.factory('album', {
      id: 'iv',
      name: 'IV',
      artist_id: 'led-zeppelin',
      artist_name: 'Led Zeppelin',
      cover: 'https://example.com/cover.jpg',
      favorite: false,
      ...overrides,
    })
  }

  const renderComponent = (album?: Album, showReleaseYear = false) => {
    album = album || createAlbum()

    const render = h.render(Component, {
      props: {
        showReleaseYear,
        album,
      },
      global: {
        stubs: {
          AlbumArtistThumbnail: h.stub('thumbnail'),
        },
      },
    })

    return {
      ...render,
      album,
    }
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('renders external album', () => {
    expect(renderComponent(createAlbum({ is_external: true })).html()).toMatchSnapshot()
  })

  it('downloads', async () => {
    const mock = h.mock(downloadService, 'fromAlbum')
    renderComponent()

    await h.user.click(screen.getByTitle('Download all songs in the album IV'))

    expect(mock).toHaveBeenCalledTimes(1)
  })

  it('does not have an option to download if downloading is disabled', async () => {
    commonStore.state.allows_download = false
    renderComponent()

    expect(screen.queryByText('Download')).toBeNull()
  })

  it('shuffles', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 10)
    const fetchMock = h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)
    const shuffleMock = h.mock(playbackService, 'queueAndPlay').mockResolvedValue(void 0)
    const { album } = renderComponent(undefined, false)

    await h.user.click(screen.getByTitle('Shuffle all songs in the album IV'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(album)
    expect(shuffleMock).toHaveBeenCalledWith(songs, true)
  })

  it('requests context menu', async () => {
    vi.mock('@/composables/useContextMenu')
    const { openContextMenu } = useContextMenu()
    const { album } = renderComponent()
    await h.trigger(screen.getByTestId('artist-album-card'), 'contextMenu')

    await assertOpenContextMenu(openContextMenu as Mock, AlbumContextMenu, { album })
  })

  it('shows release year', () => {
    renderComponent(createAlbum({ year: 1971 }), true)
    screen.getByText('1971')
  })

  it('does not show release year if not enabled via prop', () => {
    renderComponent(createAlbum({ year: 1971 }))
    expect(screen.queryByText('1971')).toBeNull()
  })

  it('if favorite, has a Favorite icon button that undoes favorite state', async () => {
    const album = createAlbum({ favorite: true })
    const toggleMock = h.mock(albumStore, 'toggleFavorite')
    renderComponent(album)

    await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))

    expect(toggleMock).toHaveBeenCalledWith(album)
  })

  it('if not favorite, does not have a Favorite icon button', async () => {
    renderComponent()
    expect(screen.queryByRole('button', { name: 'Undo Favorite' })).toBeNull()
  })
})
