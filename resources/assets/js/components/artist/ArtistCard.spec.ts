import { screen } from '@testing-library/vue'
import type { Mock } from 'vite-plus/test'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { playableStore } from '@/stores/playableStore'
import { artistStore } from '@/stores/artistStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import ArtistContextMenu from './ArtistContextMenu.vue'
import Component from './ArtistCard.vue'

vi.mock('@/composables/useContextMenu')

describe('artistCard.vue', () => {
  const h = createHarness()

  const createArtist = (overrides: Partial<Artist> = {}): Artist => {
    return h.factory('artist').make({
      id: 'led-zeppelin',
      name: 'Led Zeppelin',
      favorite: false,
      ...overrides,
    })
  }

  const renderComponent = (artist?: Artist) => {
    artist = artist || createArtist()
    const rendered = h.render(Component, {
      props: {
        artist,
      },
      global: {
        stubs: {
          AlbumArtistThumbnail: h.stub('thumbnail'),
        },
      },
    })

    return {
      ...rendered,
      artist,
    }
  }

  it('shuffles', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song').make(16)
    const fetchMock = h.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { artist } = renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Shuffle' }))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(artist)
    expect(playMock).toHaveBeenCalledWith(songs, true)
  })

  it('requests context menu', async () => {
    const { openContextMenu } = useContextMenu()
    const { artist } = renderComponent()
    await h.trigger(screen.getByTestId('artist-album-card'), 'contextMenu')

    await assertOpenContextMenu(openContextMenu as Mock, ArtistContextMenu, { artist })
  })

  it('if favorite, has a Favorite icon button that undoes favorite state', async () => {
    const artist = createArtist({ favorite: true })
    const toggleMock = h.mock(artistStore, 'toggleFavorite')
    renderComponent(artist)

    await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))

    expect(toggleMock).toHaveBeenCalledWith(artist)
  })

  it('if not favorite, does not have a Favorite icon button', async () => {
    renderComponent()
    expect(screen.queryByRole('button', { name: 'Undo Favorite' })).toBeNull()
  })
})
