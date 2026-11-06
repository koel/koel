import { screen, waitFor } from '@testing-library/vue'
import type { Mock } from 'vite-plus/test'
import { describe, expect, it, vi } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { playbackService } from '@/services/QueuePlaybackService'
import { eventBus } from '@/utils/eventBus'
import Router from '@/router'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import ArtistContextMenu from '@/components/artist/ArtistContextMenu.vue'
import Component from './ArtistScreen.vue'

vi.mock('@/composables/useContextMenu')

describe('artistScreen.vue', () => {
  const h = createHarness()

  const PlayableCardStub = defineComponent({
    props: ['playable'],
    template: '<li data-testid="playable-card" :data-id="playable.id" />',
  })

  const renderComponent = async (
    tab: '' | 'songs' | 'albums' | 'information' | 'events' = 'songs',
    artist?: Artist,
    songs = h.factory('song').make(13),
  ) => {
    commonStore.state.uses_last_fm = true

    artist =
      artist ||
      h.factory('artist').make({
        name: 'Led Zeppelin',
      })

    const resolveArtistMock = h.mock(artistStore, 'resolve').mockResolvedValue(artist)

    const fetchSongsMock = h.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)

    const rendered = h.visit(`artists/${artist.id}/${tab}`).render(Component, {
      global: {
        stubs: {
          ArtistInfo: h.stub('artist-info'),
          SongList: h.stub('song-list'),
          AlbumCard: h.stub('album-card'),
          PlayableCard: PlayableCardStub,
          ArtistEventList: h.stub('artist-event-list'),
        },
      },
    })

    await waitFor(() => {
      expect(resolveArtistMock).toHaveBeenCalledWith(artist.id)
      expect(fetchSongsMock).toHaveBeenCalledWith(artist.id)
    })

    return {
      ...rendered,
      artist,
      songs,
      resolveArtistMock,
      fetchSongsMock,
    }
  }

  it('goes back to list if artist is deleted', async () => {
    const goMock = h.mock(Router, 'go')
    const { artist } = await renderComponent()
    await h.tick()

    eventBus.emit('SONGS_UPDATED', {
      songs: [],
      artists: [],
      albums: [],
      removed: {
        album_ids: [],
        artist_ids: [artist.id, 'foo'],
      },
    })

    await waitFor(() => expect(goMock).toHaveBeenCalledWith('/#/artists'))
  })

  it('stops watching for artist removal once closed', async () => {
    const goMock = h.mock(Router, 'go')
    const { artist, unmount } = await renderComponent()
    await h.tick()

    unmount()

    eventBus.emit('SONGS_UPDATED', {
      songs: [],
      artists: [],
      albums: [],
      removed: {
        album_ids: [],
        artist_ids: [artist.id],
      },
    })

    expect(goMock).not.toHaveBeenCalled()
  })

  it('shows the most played songs with a link to all of them', async () => {
    h.mock(albumStore, 'fetchForArtist').mockResolvedValue([])
    const songs = h
      .factory('song')
      .make(12)
      .map((song, i) => ({ ...song, play_count: i }))
    const { artist } = await renderComponent('', undefined, songs)

    await waitFor(() => expect(screen.getAllByTestId('playable-card')).toHaveLength(10))

    expect(screen.getAllByTestId('playable-card').map(card => card.dataset.id)).toEqual(
      songs
        .slice(2)
        .reverse()
        .map(song => song.id),
    )

    expect(screen.getByRole('link', { name: 'Show All' }).getAttribute('href')).toBe(`/#/artists/${artist.id}/songs`)
  })

  it('has no link to all songs if every song is already shown', async () => {
    h.mock(albumStore, 'fetchForArtist').mockResolvedValue([])
    await renderComponent('', undefined, h.factory('song').make(10))

    await waitFor(() => expect(screen.getAllByTestId('playable-card')).toHaveLength(10))
    expect(screen.queryByRole('link', { name: 'Show All' })).toBeNull()
  })

  it('shuffles all songs from the overview', async () => {
    h.createAudioPlayer()
    h.mock(albumStore, 'fetchForArtist').mockResolvedValue([])
    const playMock = h.mock(playbackService, 'queueAndPlay')
    const { songs } = await renderComponent('')

    await h.user.click(await screen.findByRole('button', { name: 'Shuffle All' }))

    expect(playMock).toHaveBeenCalledWith(expect.arrayContaining(songs), true)
  })

  it('shows the albums on the overview', async () => {
    h.mock(albumStore, 'fetchForArtist').mockResolvedValue(h.factory('album').make(3))
    await renderComponent('')

    await waitFor(() => expect(screen.getAllByTestId('album-card')).toHaveLength(3))
  })

  it('shows the playable list on the songs page', async () => {
    await renderComponent()
    await waitFor(() => screen.getByTestId('song-list'))
  })

  it('has an Events section if using Ticketmaster', async () => {
    h.mock(albumStore, 'fetchForArtist').mockResolvedValue([])
    commonStore.state.uses_ticketmaster = true
    await renderComponent('')

    await waitFor(() => screen.getByTestId('artist-events'))
  })

  it('does not have an Events section if not using Ticketmaster', async () => {
    h.mock(albumStore, 'fetchForArtist').mockResolvedValue([])
    commonStore.state.uses_ticketmaster = false
    await renderComponent('')

    await waitFor(() => screen.getByTestId('artist-albums'))
    expect(screen.queryByTestId('artist-events')).toBeNull()
  })

  it('has a Favorite button if artist is favorite', async () => {
    const { artist } = await renderComponent('songs', h.factory('artist').make({ favorite: true }))
    const favoriteMock = h.mock(artistStore, 'toggleFavorite')

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))
      expect(favoriteMock).toHaveBeenCalledWith(artist)
    })
  })

  it('does not have a Favorite button if artist is not favorite', async () => {
    await renderComponent('songs', h.factory('artist').make({ favorite: false }))
    expect(screen.queryByRole('button', { name: 'Favorite' })).toBeNull()
  })

  it('requests Actions menu', async () => {
    const { openContextMenu } = useContextMenu()
    const { artist } = await renderComponent()

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'More Actions' }))
      await assertOpenContextMenu(openContextMenu as Mock, ArtistContextMenu, { artist })
    })
  })
})
