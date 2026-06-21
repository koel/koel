import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { shallowRef } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import factory from '@/__tests__/factory'
import { ContextMenuKey } from '@/config/symbols'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/QueuePlaybackService'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import EditAlbumForm from '@/components/album/EditAlbumForm.vue'
import CreateEmbedForm from '@/components/embed/CreateEmbedForm.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

import Component from './AlbumContextMenu.vue'

describe('albumContextMenu.vue', () => {
  const h = createHarness({
    beforeEach: () => openModalMock.mockClear(),
  })

  const renderComponent = async (album?: Album) => {
    if (!vi.isMockFunction(playableStore.fetchSongsForAlbum)) {
      h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue([])
    }

    album =
      album ||
      h.factory('album').make({
        name: 'IV',
        favorite: false,
        permissions: { edit: true },
      })

    const rendered = h.actingAsAdmin().render(Component, {
      props: {
        album,
      },
    })

    return {
      ...rendered,
      album,
    }
  }

  it('plays all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song').make(10)
    const fetchMock = h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { album } = await renderComponent()
    await h.user.click(screen.getByText('Play All'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(album)
    expect(playMock).toHaveBeenCalledWith(songs)
  })

  it('shuffles all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song').make(10)
    const fetchMock = h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { album } = await renderComponent()
    await h.user.click(screen.getByText('Shuffle All'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(album)
    expect(playMock).toHaveBeenCalledWith(songs, true)
  })

  it('downloads', async () => {
    const downloadMock = h.mock(downloadService, 'fromAlbum')
    const { album } = await renderComponent()

    await h.user.click(screen.getByText('Download'))

    expect(downloadMock).toHaveBeenCalledWith(album)
  })

  it('does not have an option to download if downloading is disabled', async () => {
    commonStore.state.allows_download = false
    await renderComponent()

    expect(screen.queryByText('Download')).toBeNull()
  })

  it('does not have an option to download or go to Unknown Album and Artist', async () => {
    await renderComponent(factory('album').state('unknown').make())

    expect(screen.queryByText('Go to Album')).toBeNull()
    expect(screen.queryByText('Go to Artist')).toBeNull()
    expect(screen.queryByText('Download')).toBeNull()
  })

  it('requests edit form', async () => {
    const { album } = await renderComponent()

    await h.user.click(screen.getByText('Edit…'))

    await assertOpenModal(openModalMock, EditAlbumForm, { album })
  })

  it('requests the embed form', async () => {
    const { album } = await renderComponent()
    await h.user.click(screen.getByText('Embed…'))

    await assertOpenModal(openModalMock, CreateEmbedForm, { embeddable: album })
  })

  it('does not have an option to embed when embedding is disabled', async () => {
    commonStore.state.allows_embedding = false
    await renderComponent()

    expect(screen.queryByText('Embed…')).toBeNull()
  })

  it('closes the menu after rating', async () => {
    h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue([])
    h.mock(albumStore, 'rate').mockResolvedValue()
    const menu = shallowRef<any>({ component: Component, position: { top: 0, left: 0 } })
    const album = h.factory('album').make({ rating: 0 })

    h.actingAsAdmin().render(Component, {
      props: { album },
      global: { provide: { [ContextMenuKey as symbol]: menu } },
    })

    await h.user.click(screen.getByRole('radio', { name: 'Rate 4 of 5' }))

    expect(menu.value.component).toBeNull()
  })
})
