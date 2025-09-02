import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
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

  const renderComponent = async (tab: 'songs' | 'albums' | 'information' = 'songs') => {
    commonStore.state.uses_last_fm = true

    const artist = h.factory('artist', {
      id: 'foo',
      name: 'Led Zeppelin',
    })

    const resolveArtistMock = h.mock(artistStore, 'resolve').mockResolvedValue(artist)

    const songs = h.factory('song', 13)
    const fetchSongsMock = h.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)

    await h.router.activateRoute({
      path: `artists/foo/${tab}`,
      screen: 'Artist',
    }, {
      tab,
      id: 'foo',
    })

    const rendered = h.render(Component, {
      global: {
        stubs: {
          ArtistInfo: h.stub('artist-info'),
          SongList: h.stub('song-list'),
          AlbumCard: h.stub('album-card'),
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

  it('downloads', async () => {
    const downloadMock = h.mock(downloadService, 'fromArtist')
    const { artist } = await renderComponent()

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'Download All' }))
      expect(downloadMock).toHaveBeenCalledWith(artist)
    })
  })

  it('goes back to list if artist is deleted', async () => {
    const goMock = h.mock(Router, 'go')
    const { artist } = await renderComponent()
    await h.tick()

    eventBus.emit('SONGS_UPDATED', {
      songs: [],
      artists: [],
      albums: [],
      removed: {
        albums: [],
        artists: [{
          id: artist.id,
          name: artist.name,
          image: artist.image,
          created_at: artist.created_at,
        }],
      },
    })

    await waitFor(() => expect(goMock).toHaveBeenCalledWith('/#/artists'))
  })

  it('shows the playable list', async () => {
    await renderComponent()
    await waitFor(() => screen.getByTestId('song-list'))
  })
})
