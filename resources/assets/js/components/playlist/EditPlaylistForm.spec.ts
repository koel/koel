import { ref } from 'vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import { screen, waitFor } from '@testing-library/vue'
import { ModalContextKey } from '@/symbols'
import Component from './EditPlaylistForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('submits', async () => {
      playlistFolderStore.state.folders = factory('playlist-folder', 3)

      const playlist = factory('playlist', {
        name: 'My playlist',
        folder_id: playlistFolderStore.state.folders[0].id,
      })

      playlistStore.state.playlists = [playlist]

      const updateMock = this.mock(playlistStore, 'update')

      this.render(Component, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ playlist })],
          },
        },
      })

      await this.type(screen.getByPlaceholderText('Playlist name'), 'Your playlist')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      await waitFor(() => {
        expect(updateMock).toHaveBeenCalledWith(playlist, {
          name: 'Your playlist',
          folder_id: playlist.folder_id,
        })
      })
    })
  }
}
