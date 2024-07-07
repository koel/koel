import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playlistFolderStore, playlistStore } from '@/stores'
import { ref } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { ModalContextKey } from '@/symbols'
import EditPlaylistForm from './EditPlaylistForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('submits', async () => {
      playlistFolderStore.state.folders = factory('playlist-folder', 3)

      const playlist = factory('playlist', {
        name: 'My playlist',
        folder_id: playlistFolderStore.state.folders[0].id
      })

      playlistStore.state.playlists = [playlist]

      const updateMock = this.mock(playlistStore, 'update')

      this.render(EditPlaylistForm, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ playlist })]
          }
        }
      })

      await this.type(screen.getByPlaceholderText('Playlist name'), 'Your playlist')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      await waitFor(() => {
        expect(updateMock).toHaveBeenCalledWith(playlist, {
          name: 'Your playlist',
          folder_id: playlist.folder_id
        })
      })
    })
  }
}
