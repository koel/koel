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

  const renderComponent = (playlist?: Playlist) => {
    playlist = playlist || h.factory('playlist')
    playlistStore.state.playlists = [playlist]

    const rendered = h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({ playlist }),
        },
      },
    })

    return {
      ...rendered,
      playlist,
    }
  }

  it('edits the playlist with no changes to cover', async () => {
    const updateMock = h.mock(playlistStore, 'update')
    playlistFolderStore.state.folders = h.factory('playlist-folder', 3)

    const { playlist } = renderComponent(h.factory('playlist', {
      name: 'My playlist',
      folder_id: playlistFolderStore.state.folders[0].id,
    }))

    await h.type(screen.getByRole('textbox', { name: 'name' }), 'Your playlist')
    await h.type(screen.getByRole('textbox', { name: 'description' }), 'Updated description')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(updateMock).toHaveBeenCalledWith(playlist, {
        name: 'Your playlist',
        description: 'Updated description',
        folder_id: playlist.folder_id,
        cover: null,
      })
    })
  })

  it('removes the cover', async () => {
    const updateMock = h.mock(playlistStore, 'update')
    const removeCoverMock = h.mock(playlistStore, 'removeCover')

    const { playlist } = renderComponent(h.factory('playlist', {
      name: 'My playlist',
      cover: 'https://localhost:3000/img/storage/cover.webp',
    }))

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))
    expect(removeCoverMock).toHaveBeenCalledWith(playlist)

    await h.type(screen.getByRole('textbox', { name: 'name' }), 'Your playlist')
    await h.type(screen.getByRole('textbox', { name: 'description' }), 'Updated description')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(updateMock).toHaveBeenCalledWith(playlist, {
        name: 'Your playlist',
        description: 'Updated description',
        folder_id: playlist.folder_id,
        cover: null,
      })
    })
  })

  it('removes and replaces the cover', async () => {
    const updateMock = h.mock(playlistStore, 'update')
    const removeCoverMock = h.mock(playlistStore, 'removeCover')

    const { playlist } = renderComponent(h.factory('playlist', {
      name: 'My playlist',
      cover: 'https://localhost:3000/img/storage/cover.webp',
    }))

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))
    expect(removeCoverMock).toHaveBeenCalledWith(playlist)

    await h.user.upload(
      screen.getByLabelText('Pick a cover (optional)'),
      new File(['bytes'], 'cover.png', { type: 'image/png' }),
    )

    await waitFor(() => screen.getByAltText('Cover'))

    await h.type(screen.getByRole('textbox', { name: 'name' }), 'Your playlist')
    await h.type(screen.getByRole('textbox', { name: 'description' }), 'Updated description')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(updateMock).toHaveBeenCalledWith(playlist, {
        name: 'Your playlist',
        description: 'Updated description',
        folder_id: playlist.folder_id,
        cover: 'data:image/png;base64,Ynl0ZXM=',
      })
    })
  })
})
