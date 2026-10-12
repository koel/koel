import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import Component from './CreateSmartPlaylistForm.vue'

describe('createSmartPlaylistForm', () => {
  const h = createHarness()

  const renderComponent = (folder?: PlaylistFolder | null) => {
    playlistFolderStore.state.folders = h.factory('playlist-folder').make(2)

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

  it('adds a rule group when "Group" button is clicked', async () => {
    renderComponent()

    await h.user.click(screen.getByText('Rules'))
    await h.user.click(screen.getByRole('button', { name: 'Group' }))

    await waitFor(() => {
      screen.getByText(/Include songs that match/)
    })
  })

  it('shows the Rules tab when one of its fields is invalid', async () => {
    renderComponent()

    const ruleValue = await screen.findByRole('textbox', { name: 'value[]', hidden: true })
    ruleValue.dispatchEvent(new Event('invalid'))

    await waitFor(() => expect(screen.getByRole('tab', { name: 'Rules' }).getAttribute('aria-selected')).toBe('true'))
  })

  it('cannot be saved once its last rule is removed', async () => {
    renderComponent()

    await h.user.click(screen.getByText('Rules'))
    await h.user.click(await screen.findByTestId('remove-rule-btn'))

    expect(screen.getByRole('button', { name: 'Save' }).hasAttribute('disabled')).toBe(true)
  })

  it('submits the rule it starts with', async () => {
    const playlist = h.factory('playlist').make()
    const storeMock = h.mock(playlistStore, 'store').mockResolvedValue(playlist)
    renderComponent()

    await h.type(screen.getByRole('textbox', { name: 'name' }), 'Rock Playlist')
    await h.user.click(screen.getByText('Rules'))
    await h.type(await screen.findByRole('textbox', { name: 'value[]' }), 'Rock')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      const submittedData = storeMock.mock.calls[0][0]
      expect(submittedData.name).toBe('Rock Playlist')
      expect(submittedData.rules).toHaveLength(1)
      expect(submittedData.rules[0].rules[0].value).toEqual(['Rock'])
    })
  })

  it('pre-selects folder when folder prop is provided', () => {
    const folder = h.factory('playlist-folder').make()
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
