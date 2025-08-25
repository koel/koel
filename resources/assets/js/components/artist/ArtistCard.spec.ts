import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/QueuePlaybackService'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { eventBus } from '@/utils/eventBus'
import { artistStore } from '@/stores/artistStore'
import Component from './ArtistCard.vue'

describe('artistCard.vue', () => {
  const h = createHarness()

  const createArtist = (overrides: Partial<Artist> = {}): Artist => {
    return h.factory('artist', {
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

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('renders external artist', () => {
    expect(renderComponent(createArtist({ is_external: true })).html()).toMatchSnapshot()
  })

  it('downloads', async () => {
    const mock = h.mock(downloadService, 'fromArtist')
    renderComponent()

    await h.user.click(screen.getByTitle('Download all songs by Led Zeppelin'))
    expect(mock).toHaveBeenCalledOnce()
  })

  it('does not have an option to download if downloading is disabled', async () => {
    commonStore.state.allows_download = false
    renderComponent()

    expect(screen.queryByText('Download')).toBeNull()
  })

  it('shuffles', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 16)
    const fetchMock = h.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { artist } = renderComponent()

    await h.user.click(screen.getByTitle('Shuffle all songs by Led Zeppelin'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(artist)
    expect(playMock).toHaveBeenCalledWith(songs, true)
  })

  it('requests context menu', async () => {
    const { artist } = renderComponent()
    const emitMock = h.mock(eventBus, 'emit')
    await h.trigger(screen.getByTestId('artist-album-card'), 'contextMenu')

    expect(emitMock).toHaveBeenCalledWith('ARTIST_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), artist)
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
