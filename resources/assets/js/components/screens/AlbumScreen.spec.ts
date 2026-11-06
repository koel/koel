import { screen, waitFor, within } from '@testing-library/vue'
import type { Mock } from 'vite-plus/test'
import { describe, expect, it, vi } from 'vite-plus/test'
import type { Component as VueComponent } from 'vue'
import { defineComponent, reactive } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { eventBus } from '@/utils/eventBus'
import Router from '@/router'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import AlbumContextMenu from '@/components/album/AlbumContextMenu.vue'
import Component from './AlbumScreen.vue'

const mockHandleHttpError = vi.fn()

vi.mock('@/composables/useContextMenu')

vi.mock('@/composables/useErrorHandler', () => ({
  useErrorHandler: () => ({
    handleHttpError: mockHandleHttpError,
  }),
}))

describe('albumScreen.vue', () => {
  const h = createHarness()

  const EmptyInfoStub = defineComponent({
    props: ['album'],
    emits: ['empty'],
    mounted() {
      if (!this.album.description) {
        this.$emit('empty')
      }
    },
    template: '<br />',
  })

  const renderComponent = async (
    album?: Album,
    otherAlbums: Album[] = [],
    infoStub: VueComponent = h.stub('album-info'),
  ) => {
    album =
      album ||
      h.factory('album').make({
        name: 'Led Zeppelin IV',
        artist_id: 'bar',
        artist_name: 'Led Zeppelin',
      })

    const resolveAlbumMock = h.mock(albumStore, 'resolve').mockResolvedValue(album)

    const songs = h.factory('song').make(13)
    const fetchSongsMock = h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)
    const fetchOtherAlbumsMock = h.mock(albumStore, 'fetchForArtist').mockResolvedValue([...otherAlbums, album])

    h.visit(`albums/${album.id}`)

    const rendered = h.actingAsAdmin().render(Component, {
      global: {
        stubs: {
          AlbumCard: h.stub('album-card'),
          AlbumInfo: infoStub,
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
      fetchOtherAlbumsMock,
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

  it('goes to the kept album if the shown album is merged into it', async () => {
    const goMock = h.mock(Router, 'go')
    const { album } = await renderComponent()
    await h.tick()
    const keptAlbum = h.factory('album').make()

    eventBus.emit('ALBUM_MERGED', album, keptAlbum)

    await waitFor(() => expect(goMock).toHaveBeenCalledWith(`/#/albums/${keptAlbum.id}`))
  })

  it('stops watching for album removal once closed', async () => {
    const goMock = h.mock(Router, 'go')
    const { album, unmount } = await renderComponent()
    await h.tick()

    unmount()

    eventBus.emit('SONGS_UPDATED', {
      songs: [],
      artists: [],
      albums: [],
      removed: {
        album_ids: [album.id],
        artist_ids: [],
      },
    })

    expect(goMock).not.toHaveBeenCalled()
  })

  it('shows every track', async () => {
    await renderComponent()
    await waitFor(() => expect(screen.getAllByTestId('song-item')).toHaveLength(13))
  })

  it('shows other albums from the same artist', async () => {
    const { album, fetchOtherAlbumsMock } = await renderComponent(undefined, h.factory('album').make(3))

    await waitFor(() => {
      expect(fetchOtherAlbumsMock).toHaveBeenCalledWith(album.artist_id)
      expect(screen.getAllByTestId('album-card')).toHaveLength(3)
    })
  })

  it('hides the other albums section if the artist has no other albums', async () => {
    const { fetchOtherAlbumsMock } = await renderComponent()

    await waitFor(() => expect(fetchOtherAlbumsMock).toHaveBeenCalled())
    await h.tick()

    expect(screen.queryByTestId('album-other-albums')).toBeNull()
  })

  it('hides the other albums section if the albums fail to load', async () => {
    const error = new Error('Failed')
    h.mock(albumStore, 'fetchForArtist').mockRejectedValueOnce(error)
    await renderComponent()

    await waitFor(() => expect(mockHandleHttpError).toHaveBeenCalledWith(error))
    await h.tick()

    expect(screen.queryByTestId('album-other-albums')).toBeNull()
  })

  it('shows the About section if album information is available', async () => {
    commonStore.state.uses_last_fm = true
    await renderComponent()

    await waitFor(() => screen.getByTestId('album-about'))
  })

  it('hides the About section if no album information is available', async () => {
    commonStore.state.uses_last_fm = false
    commonStore.state.uses_musicbrainz = false
    await renderComponent()
    await h.tick()

    expect(screen.queryByTestId('album-about')).toBeNull()
  })

  it('hides the About section if the information turns out to be empty', async () => {
    commonStore.state.uses_last_fm = true
    await renderComponent(undefined, [], EmptyInfoStub)

    await h.tick(2)

    expect(screen.queryByTestId('album-about')).toBeNull()
  })

  it('shows the About section again once the description is edited', async () => {
    commonStore.state.uses_last_fm = true
    const album = reactive(h.factory('album').make())
    await renderComponent(album, [], EmptyInfoStub)
    await h.tick(2)

    album.description = 'A new description'

    await waitFor(() => screen.getByTestId('album-about'))
  })

  it('has a Favorite button if album is favorite', async () => {
    const { album } = await renderComponent(h.factory('album').make({ favorite: true }))
    const favoriteMock = h.mock(albumStore, 'toggleFavorite')

    await waitFor(async () => {
      await h.user.click(
        within(screen.getByTestId('song-list-controls')).getByRole('button', { name: 'Undo Favorite' }),
      )
      expect(favoriteMock).toHaveBeenCalledWith(album)
    })
  })

  it('does not have a Favorite button if album is not favorite', async () => {
    await renderComponent(h.factory('album').make({ favorite: false }))
    const controls = await screen.findByTestId('song-list-controls')

    expect(within(controls).queryByRole('button', { name: 'Favorite' })).toBeNull()
  })

  it('requests Actions menu', async () => {
    const { openContextMenu } = useContextMenu()
    const { album } = await renderComponent()

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'More Actions' }))
      await assertOpenContextMenu(openContextMenu as Mock, AlbumContextMenu, { album })
    })
  })
})
