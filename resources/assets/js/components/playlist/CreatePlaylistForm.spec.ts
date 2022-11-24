import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playlistStore } from '@/stores'
import factory from '@/__tests__/factory'
import CreatePlaylistForm from './CreatePlaylistForm.vue'
import { ref } from 'vue'
import { ModalContextKey } from '@/symbols'

new class extends UnitTestCase {
  protected test () {
    it('submits', async () => {
      const folder = factory<PlaylistFolder>('playlist-folder')
      const storeMock = this.mock(playlistStore, 'store').mockResolvedValue(factory<Playlist>('playlist'))
      const { getByPlaceholderText, getByRole } = await this.render(CreatePlaylistForm, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ folder })]
          }
        }
      })

      await fireEvent.update(getByPlaceholderText('Playlist name'), 'My playlist')
      await fireEvent.click(getByRole('button', { name: 'Save' }))

      expect(storeMock).toHaveBeenCalledWith('My playlist', {
        folder_id: folder.id
      })
    })
  }
}
