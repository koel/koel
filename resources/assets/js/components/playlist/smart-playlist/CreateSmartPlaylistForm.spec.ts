import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import Component from './CreateSmartPlaylistForm.vue'

describe('createSmartPlaylistForm', () => {
  const h = createHarness()

  const renderComponent = (folder?: PlaylistFolder | null) => {
    playlistFolderStore.state.folders = h.factory('playlist-folder', 2)

    return h.render(Component, {
      props: {
        folder: folder ?? null,
      },
    })
  }

  it('renders the Details tab by default', () => {
    renderComponent()

    screen.getByText('New Smart Playlist')
    screen.getByRole('textbox', { name: 'name' })
    screen.getByRole('textbox', { name: 'description' })
  })

  it('switches to Rules tab on click', async () => {
    renderComponent()

    await h.user.click(screen.getByText('Rules'))

    screen.getByTitle('Add a new group')
  })

  it('adds a rule group when "Group" button is clicked', async () => {
    renderComponent()

    await h.user.click(screen.getByText('Rules'))
    await h.user.click(screen.getByTitle('Add a new group'))

    await waitFor(() => {
      screen.getByText(/Include songs that match/)
    })
  })

  it('submits form data with empty rules when no groups added', async () => {
    const playlist = h.factory('playlist')
    const storeMock = h.mock(playlistStore, 'store').mockResolvedValue(playlist)
    renderComponent()

    await h.type(screen.getByRole('textbox', { name: 'name' }), 'My Smart Playlist')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(storeMock).toHaveBeenCalledWith(
        expect.objectContaining({
          name: 'My Smart Playlist',
          rules: [],
        }),
      )
    })
  })

  it('submits form data with rule groups when groups are added', async () => {
    const playlist = h.factory('playlist')
    const storeMock = h.mock(playlistStore, 'store').mockResolvedValue(playlist)
    renderComponent()

    // Add a rule group via the Rules tab
    await h.user.click(screen.getByText('Rules'))
    await h.user.click(screen.getByTitle('Add a new group'))

    await waitFor(() => {
      screen.getByText(/Include songs that match/)
    })

    // Switch back to Details to fill in the name
    await h.user.click(screen.getByText('Details'))
    await h.type(screen.getByRole('textbox', { name: 'name' }), 'Rock Playlist')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(storeMock).toHaveBeenCalled()
      const submittedData = storeMock.mock.calls[0][0]
      expect(submittedData.name).toBe('Rock Playlist')
      expect(submittedData.rules).toHaveLength(1)
      expect(submittedData.rules[0].rules).toHaveLength(1)
    })
  })

  it('pre-selects folder when folder prop is provided', () => {
    const folder = h.factory('playlist-folder')
    playlistFolderStore.state.folders = [folder]

    h.render(Component, {
      props: { folder },
    })

    // The folder select should have the folder's id selected
    const folderOption = screen.getByRole('option', { name: folder.name }) as HTMLOptionElement
    expect(folderOption.selected).toBe(true)
  })

  it('closes without confirmation when pristine', async () => {
    const { emitted } = renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Cancel' }))

    expect(emitted().close).toBeTruthy()
  })
})
