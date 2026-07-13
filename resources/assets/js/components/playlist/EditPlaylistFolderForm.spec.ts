import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import Component from './EditPlaylistFolderForm.vue'

describe('editPlaylistFolderForm.vue', () => {
  const h = createHarness()

  it('updates the name and parent together', async () => {
    const folder = h.factory('playlist-folder').make({ name: 'My folder', parent_id: null })
    const parent = h.factory('playlist-folder').make({ name: 'Parent folder', parent_id: null })
    playlistFolderStore.init([folder, parent])
    const updateMock = h.mock(playlistFolderStore, 'update')
    h.render(Component, {
      props: {
        folder,
      },
    })

    await h.type(screen.getByPlaceholderText('Folder name'), 'Your folder')
    await h.user.selectOptions(screen.getByRole('combobox', { name: 'Parent Folder' }), parent.id)
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(updateMock).toHaveBeenCalledWith(folder, {
        name: 'Your folder',
        parent_id: parent.id,
      })
    })
  })

  it('lists valid parents by full path', () => {
    const root = h.factory('playlist-folder').make({ name: 'Music', parent_id: null })
    const folder = h.factory('playlist-folder').make({ name: 'Live', parent_id: root.id })
    const descendant = h.factory('playlist-folder').make({ name: '2026', parent_id: folder.id })
    const otherRoot = h.factory('playlist-folder').make({ name: 'Collections', parent_id: null })
    const validNestedParent = h.factory('playlist-folder').make({ name: 'Favorites', parent_id: otherRoot.id })
    playlistFolderStore.init([root, folder, descendant, otherRoot, validNestedParent])

    h.render(Component, {
      props: {
        folder,
      },
    })

    expect(screen.getAllByRole('option').map(option => option.textContent?.trim())).toEqual([
      'Root',
      'Collections',
      'Collections / Favorites',
      'Music',
    ])
  })
})
