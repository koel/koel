import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { resourcePermissionService } from '@/services/resourcePermissionService'
import { eventBus } from '@/utils/eventBus'
import Router from '@/router'
import Component from './AlbumScreen.vue'

describe('albumScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      h.mock(resourcePermissionService, 'check').mockResolvedValue(true)
    },
  })

  const renderComponent = async (
    tab: 'songs' | 'other-albums' | 'information' = 'songs',
    album?: Album,
  ) => {
    commonStore.state.uses_last_fm = true

    album = album || h.factory('album', {
      id: 'foo',
      name: 'Led Zeppelin IV',
      artist_id: 'bar',
      artist_name: 'Led Zeppelin',
    })

    const resolveAlbumMock = h.mock(albumStore, 'resolve').mockResolvedValue(album)

    const songs = h.factory('song', 13)
    const fetchSongsMock = h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)

    await h.router.activateRoute({
      path: `albums/${album.id}/${tab}`,
      screen: 'Album',
    }, {
      tab,
      id: album.id,
    })

    const rendered = h.beAdmin().render(Component, {
      global: {
        stubs: {
          SongList: h.stub('song-list'),
          AlbumCard: h.stub('album-card'),
          AlbumInfo: h.stub('album-info'),
        },
      },
    })

    await waitFor(() => {
      expect(resolveAlbumMock).toHaveBeenCalledWith(album.id)
      expect(fetchSongsMock).toHaveBeenCalledWith(album.id)
    })

    return {
      ...rendered,
      album,
      songs,
      resolveAlbumMock,
      fetchSongsMock,
    }
  }

  it('goes back to list if album is deleted', async () => {
    const goMock = h.mock(Router, 'go')
    const { album } = await renderComponent()
    await h.tick()

    eventBus.emit('SONGS_UPDATED', {
      songs: [],
      artists: [],
      albums: [],
      removed: {
        album_ids: [album.id, 'foo'],
        artist_ids: [],
      },
    })

    await waitFor(() => expect(goMock).toHaveBeenCalledWith('/#/albums'))
  })

  it('shows the song list', async () => {
    await renderComponent()
    await waitFor(async () => screen.getByTestId('song-list'))
  })

  it('shows other albums from the same artist', async () => {
    const albums = h.factory('album', 3)
    const fetchMock = h.mock(albumStore, 'fetchForArtist').mockResolvedValue(albums)
    const { album } = await renderComponent('other-albums')

    albums.push(album)

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(album.artist_id)
      expect(screen.getAllByTestId('album-card')).toHaveLength(3) // current album is excluded
    })
  })

  it('has a Favorite button if album is favorite', async () => {
    const { album } = await renderComponent('songs', h.factory('album', { favorite: true }))
    const favoriteMock = h.mock(albumStore, 'toggleFavorite')

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))
      expect(favoriteMock).toHaveBeenCalledWith(album)
    })
  })

  it('does not have a Favorite button if album is not favorite', async () => {
    await renderComponent('songs', h.factory('album', { favorite: false }))
    expect(screen.queryByRole('button', { name: 'Favorite' })).toBeNull()
  })

  it('requests Actions menu', async () => {
    const { album } = await renderComponent()
    const emitMock = h.mock(eventBus, 'emit')

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'More Actions' }))
      expect(emitMock).toHaveBeenCalledWith('ALBUM_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), album)
    })
  })
})
