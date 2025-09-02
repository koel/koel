import { ref } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { ModalContextKey } from '@/symbols'
import Component from './EditPlaylistFolderForm.vue'

describe('editPlaylistFolderForm.vue', () => {
  const h = createHarness()

  it('submits', async () => {
    const folder = h.factory('playlist-folder', { name: 'My folder' })
    const renameMock = h.mock(playlistFolderStore, 'rename')
    h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({ folder }),
        },
      },
    })

    await h.type(screen.getByPlaceholderText('Folder name'), 'Your folder')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(renameMock).toHaveBeenCalledWith(folder, 'Your folder')
    })
  })
})
