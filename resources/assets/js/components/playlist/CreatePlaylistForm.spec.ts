import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playlistStore } from '@/stores'
import factory from '@/__tests__/factory'
import { ref } from 'vue'
import { ModalContextKey } from '@/symbols'
import CreatePlaylistForm from './CreatePlaylistForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('submits', async () => {
      const folder = factory<PlaylistFolder>('playlist-folder')
      const storeMock = this.mock(playlistStore, 'store').mockResolvedValue(factory<Playlist>('playlist'))
      this.render(CreatePlaylistForm, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ folder })]
          }
        }
      })

      await this.type(screen.getByPlaceholderText('Playlist name'), 'My playlist')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(storeMock).toHaveBeenCalledWith('My playlist', {
        folder_id: folder.id
      })
    })
  }
}
