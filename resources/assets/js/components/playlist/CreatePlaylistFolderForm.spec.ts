import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import Component from './CreatePlaylistFolderForm.vue'

describe('createPlaylistFolderForm.vue', () => {
  const h = createHarness()

  it('submits', async () => {
    const storeMock = h.mock(playlistFolderStore, 'store').mockResolvedValue(h.factory('playlist-folder').make())

    h.render(Component)

    await h.type(screen.getByPlaceholderText('Folder name'), 'My folder')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(storeMock).toHaveBeenCalledWith('My folder')
  })

  it('creates a folder under the provided parent', async () => {
    const parent = h.factory('playlist-folder').make()
    const storeMock = h.mock(playlistFolderStore, 'store').mockResolvedValue(h.factory('playlist-folder').make())

    h.render(Component, {
      props: { parent },
    })

    await h.type(screen.getByPlaceholderText('Folder name'), 'Child folder')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(storeMock).toHaveBeenCalledWith('Child folder', parent)
  })
})
