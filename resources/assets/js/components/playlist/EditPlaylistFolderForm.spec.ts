import { ref } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playlistFolderStore } from '@/stores'
import { ModalContextKey } from '@/symbols'
import EditPlaylistFolderForm from './EditPlaylistFolderForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('submits', async () => {
      const folder = factory<PlaylistFolder>('playlist-folder', { name: 'My folder' })
      const renameMock = this.mock(playlistFolderStore, 'rename')
      this.render(EditPlaylistFolderForm, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ folder })]
          }
        }
      })

      await this.type(screen.getByPlaceholderText('Folder name'), 'Your folder')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      await waitFor(() => {
        expect(renameMock).toHaveBeenCalledWith(folder, 'Your folder')
      })
    })
  }
}
