import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import Component from './CreatePlaylistFolderForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('submits', async () => {
      const storeMock = this.mock(playlistFolderStore, 'store')
        .mockResolvedValue(factory('playlist-folder'))

      this.render(Component)

      await this.type(screen.getByPlaceholderText('Folder name'), 'My folder')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(storeMock).toHaveBeenCalledWith('My folder')
    })
  }
}
