import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { shallowRef } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import factory from '@/__tests__/factory'
import { ContextMenuKey } from '@/config/symbols'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/QueuePlaybackService'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import CreateEmbedForm from '@/components/embed/CreateEmbedForm.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

import Component from './ArtistContextMenu.vue'

describe('artistContextMenu.vue', () => {
  const h = createHarness({
    beforeEach: () => openModalMock.mockClear(),
  })

  const renderComponent = async (artist?: Artist) => {
    artist =
      artist ||
      h.factory('artist').make({
        name: 'Accept',
        favorite: false,
        permissions: { edit: true },
      })

    const rendered = h.render(Component, {
      props: {
        artist,
      },
    })

    return {
      ...rendered,
      artist,
    }
  }

  it('plays all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song').make(10)
    const fetchMock = h.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { artist } = await renderComponent()
    await screen.getByText('Play All').click()
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(artist)
    expect(playMock).toHaveBeenCalledWith(songs)
  })

  it('shuffles all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song').make(10)
    const fetchMock = h.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { artist } = await renderComponent()
    await screen.getByText('Shuffle All').click()
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(artist)
    expect(playMock).toHaveBeenCalledWith(songs, true)
  })

  it('downloads', async () => {
    const mock = h.mock(downloadService, 'fromArtist')

    const { artist } = await renderComponent()
    await screen.getByText('Download').click()

    expect(mock).toHaveBeenCalledWith(artist)
  })

  it('does not have an option to download if downloading is disabled', async () => {
    commonStore.state.allows_download = false
    await renderComponent()

    expect(screen.queryByText('Download')).toBeNull()
  })

  it('does not have an option to download Unknown Artist', async () => {
    await renderComponent(factory('artist').state('unknown').make())

    expect(screen.queryByText('Download')).toBeNull()
  })

  it('does not have an option to download Various Artist', async () => {
    await renderComponent(factory('artist').state('various').make())
    expect(screen.queryByText('Download')).toBeNull()
  })

  it('requests the embed form', async () => {
    const { artist } = await renderComponent()
    await h.user.click(screen.getByText('Embed…'))

    await assertOpenModal(openModalMock, CreateEmbedForm, { embeddable: artist })
  })

  it('does not have an option to embed when embedding is disabled', async () => {
    commonStore.state.allows_embedding = false
    await renderComponent()

    expect(screen.queryByText('Embed…')).toBeNull()
  })

  it('closes the menu after rating', async () => {
    h.mock(artistStore, 'rate').mockResolvedValue(undefined)
    const menu = shallowRef<any>({ component: Component, position: { top: 0, left: 0 } })
    const artist = h.factory('artist').make({ rating: 0 })

    h.render(Component, {
      props: { artist },
      global: { provide: { [ContextMenuKey as symbol]: menu } },
    })

    await h.user.click(screen.getByRole('radio', { name: 'Rate 4 of 5' }))

    expect(menu.value.component).toBeNull()
  })
})
