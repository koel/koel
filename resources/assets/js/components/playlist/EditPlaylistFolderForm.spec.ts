import { ref } from 'vue'
import { fireEvent, waitFor } from '@testing-library/vue'
import { expect, it, vi } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playlistFolderStore } from '@/stores'
import { PlaylistFolderKey } from '@/symbols'
import EditPlaylistFolderForm from './EditPlaylistFolderForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('submits', async () => {
      const folder = factory<PlaylistFolder>('playlist-folder', { name: 'My folder' })
      const updateFolderNameMock = vi.fn()
      const renameMock = this.mock(playlistFolderStore, 'rename')
      const { getByPlaceholderText, getByRole } = this.render(EditPlaylistFolderForm, {
        global: {
          provide: {
            [<symbol>PlaylistFolderKey]: [ref(folder), updateFolderNameMock]
          }
        }
      })

      await fireEvent.update(getByPlaceholderText('Folder name'), 'Your folder')
      await fireEvent.click(getByRole('button', { name: 'Save' }))

      await waitFor(() => {
        expect(renameMock).toHaveBeenCalledWith(folder, 'Your folder')
        expect(updateFolderNameMock).toHaveBeenCalledWith('Your folder')
      })
    })
  }
}
