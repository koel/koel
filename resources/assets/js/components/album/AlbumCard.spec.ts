import { screen } from '@testing-library/vue'
import type { Mock } from 'vite-plus/test'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { playableStore } from '@/stores/playableStore'
import { albumStore } from '@/stores/albumStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import AlbumContextMenu from './AlbumContextMenu.vue'
import Component from './AlbumCard.vue'

vi.mock('@/composables/useContextMenu')

describe('albumCard', () => {
  const h = createHarness()

  const createAlbum = (overrides: Partial<Album> = {}) => {
    return h.factory('album').make({
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

  it('links the album artist to their page', () => {
    renderComponent()

    expect(screen.getByRole('link', { name: 'Led Zeppelin' })).toHaveProperty(
      'href',
      expect.stringContaining('/artists/led-zeppelin'),
    )
  })

  it('shows a compilation album artist without a link', () => {
    renderComponent(createAlbum({ artist_id: 'various', artist_name: 'Various Artists' }))

    screen.getByText('Various Artists')
    expect(screen.queryByRole('link', { name: 'Various Artists' })).toBeNull()
  })

  it('shuffles', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song').make(10)
    const fetchMock = h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)
    const shuffleMock = h.mock(playbackService, 'queueAndPlay').mockResolvedValue(void 0)
    const { album } = renderComponent(undefined, false)

    await h.user.click(screen.getByRole('button', { name: 'Shuffle' }))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(album)
    expect(shuffleMock).toHaveBeenCalledWith(songs, true)
  })

  it('requests context menu', async () => {
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
