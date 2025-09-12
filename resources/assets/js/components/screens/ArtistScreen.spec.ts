import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { eventBus } from '@/utils/eventBus'
import { resourcePermissionService } from '@/services/resourcePermissionService'
import Router from '@/router'
import Component from './ArtistScreen.vue'

describe('artistScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      h.mock(resourcePermissionService, 'check').mockResolvedValue(true)
    },
  })

  const renderComponent = async (
    tab: 'songs' | 'albums' | 'information' | 'events' = 'songs',
    artist?: Artist,
  ) => {
    commonStore.state.uses_last_fm = true

    artist = artist || h.factory('artist', {
      id: 'foo',
      name: 'Led Zeppelin',
    })

    const resolveArtistMock = h.mock(artistStore, 'resolve').mockResolvedValue(artist)

    const songs = h.factory('song', 13)
    const fetchSongsMock = h.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)

    await h.router.activateRoute({
      path: `artists/${artist.id}/${tab}`,
      screen: 'Artist',
    }, {
      tab,
      id: artist.id,
    })

    const rendered = h.render(Component, {
      global: {
        stubs: {
          ArtistInfo: h.stub('artist-info'),
          SongList: h.stub('song-list'),
          AlbumCard: h.stub('album-card'),
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

  it('shows the playable list', async () => {
    await renderComponent()
    await waitFor(() => screen.getByTestId('song-list'))
  })

  it('has an Events tab if using Ticketmaster', async () => {
    commonStore.state.uses_ticketmaster = true
    await renderComponent()

    await waitFor(() => screen.getByRole('link', { name: 'Events' }))
  })

  it('does not have an Events tab if not using Ticketmaster', async () => {
    commonStore.state.uses_ticketmaster = false
    await renderComponent()

    await waitFor(() => expect(screen.queryByRole('link', { name: 'Events' })).toBeNull())
  })

  it('has a Favorite button if artist is favorite', async () => {
    const { artist } = await renderComponent('songs', h.factory('artist', { favorite: true }))
    const favoriteMock = h.mock(artistStore, 'toggleFavorite')

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))
      expect(favoriteMock).toHaveBeenCalledWith(artist)
    })
  })

  it('does not have a Favorite button if artist is not favorite', async () => {
    await renderComponent('songs', h.factory('artist', { favorite: false }))
    expect(screen.queryByRole('button', { name: 'Favorite' })).toBeNull()
  })

  it('requests Actions menu', async () => {
    const { artist } = await renderComponent()
    const emitMock = h.mock(eventBus, 'emit')

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'More Actions' }))
      expect(emitMock).toHaveBeenCalledWith('ARTIST_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), artist)
    })
  })
})
