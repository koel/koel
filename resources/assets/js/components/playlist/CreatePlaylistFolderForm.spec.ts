import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playlistFolderStore } from '@/stores'
import factory from '@/__tests__/factory'
import CreatePlaylistFolderForm from './CreatePlaylistFolderForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('submits', async () => {
      const storeMock = this.mock(playlistFolderStore, 'store')
        .mockResolvedValue(factory<PlaylistFolder>('playlist-folder'))

      await this.render(CreatePlaylistFolderForm)

      await this.type(screen.getByPlaceholderText('Folder name'), 'My folder')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(storeMock).toHaveBeenCalledWith('My folder')
    })
  }
}
