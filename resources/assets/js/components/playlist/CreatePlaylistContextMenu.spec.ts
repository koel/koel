import { describe, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import CreatePlaylistForm from '@/components/playlist/CreatePlaylistForm.vue'
import CreateSmartPlaylistForm from '@/components/playlist/smart-playlist/CreateSmartPlaylistForm.vue'
import CreatePlaylistFolderForm from '@/components/playlist/CreatePlaylistFolderForm.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

import Component from './CreatePlaylistContextMenu.vue'

describe('createPlaylistContextMenu.vue', () => {
  const h = createHarness({
    beforeEach: () => openModalMock.mockClear(),
  })

  it('opens CreatePlaylistForm when clicking New Playlist', async () => {
    h.render(Component)
    await h.user.click(screen.getByText('New Playlist…'))

    await assertOpenModal(openModalMock, CreatePlaylistForm, { folder: null, playables: [] })
  })

  it('opens CreateSmartPlaylistForm when clicking New Smart Playlist', async () => {
    h.render(Component)
    await h.user.click(screen.getByText('New Smart Playlist…'))

    await assertOpenModal(openModalMock, CreateSmartPlaylistForm, { folder: null })
  })

  it('opens CreatePlaylistFolderForm when clicking New Folder', async () => {
    h.render(Component)
    await h.user.click(screen.getByText('New Folder…'))

    await assertOpenModal(openModalMock, CreatePlaylistFolderForm)
  })
})
