import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import { screen, waitFor } from '@testing-library/vue'
import { ModalContextKey } from '@/symbols'
import Component from './EditPlaylistForm.vue'

describe('editPlaylistForm.vue', () => {
  const h = createHarness()

  it('submits', async () => {
    playlistFolderStore.state.folders = h.factory('playlist-folder', 3)

    const playlist = h.factory('playlist', {
      name: 'My playlist',
      folder_id: playlistFolderStore.state.folders[0].id,
    })

    playlistStore.state.playlists = [playlist]

    const updateMock = h.mock(playlistStore, 'update')

    h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: [ref({ playlist })],
        },
      },
    })

    await h.type(screen.getByPlaceholderText('Playlist name'), 'Your playlist')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(updateMock).toHaveBeenCalledWith(playlist, {
        name: 'Your playlist',
        folder_id: playlist.folder_id,
      })
    })
  })
})
