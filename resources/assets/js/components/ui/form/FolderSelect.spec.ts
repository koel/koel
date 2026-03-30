import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import Component from './FolderSelect.vue'

describe('folderSelect', () => {
  const h = createHarness()

  const renderComponent = (folderId: PlaylistFolder['id'] | null = null) => {
    playlistFolderStore.state.folders = h.factory('playlist-folder', 3)

    return h.render(Component, {
      props: {
        folderId,
        'onUpdate:folderId': (value: any) => value,
        folderName: null,
        'onUpdate:folderName': (value: any) => value,
      },
    })
  }

  it('renders existing folders in the dropdown', () => {
    renderComponent()

    const options = screen.getAllByRole('option')
    // empty option + 3 folders + "+ New Folder"
    expect(options).toHaveLength(5)
    expect(options[options.length - 1].textContent).toContain('+ New Folder')
  })

  it('switches to input mode when "+ New Folder" is selected', async () => {
    renderComponent()

    await h.user.selectOptions(screen.getByRole('combobox'), '__new__')

    await waitFor(() => {
      screen.getByPlaceholderText('Folder name')
      screen.getByTitle('Create')
      screen.getByTitle('Cancel')
    })
  })

  it('emits folder name on confirm', async () => {
    const { emitted } = renderComponent()

    await h.user.selectOptions(screen.getByRole('combobox'), '__new__')

    await waitFor(() => screen.getByPlaceholderText('Folder name'))
    await h.user.type(screen.getByPlaceholderText('Folder name'), 'My Folder')
    await h.user.click(screen.getByTitle('Create'))

    await waitFor(() => {
      expect(emitted()['update:folderName']).toBeTruthy()
      const lastEmit = emitted()['update:folderName'].at(-1)
      expect(lastEmit).toEqual(['My Folder'])
    })
  })

  it('reverts to dropdown on cancel', async () => {
    renderComponent()

    await h.user.selectOptions(screen.getByRole('combobox'), '__new__')

    await waitFor(() => screen.getByPlaceholderText('Folder name'))
    await h.user.click(screen.getByTitle('Cancel'))

    await waitFor(() => {
      screen.getByRole('combobox')
    })
  })

  it('does not confirm when folder name is empty', async () => {
    const { emitted } = renderComponent()

    await h.user.selectOptions(screen.getByRole('combobox'), '__new__')

    await waitFor(() => screen.getByPlaceholderText('Folder name'))
    await h.user.click(screen.getByTitle('Create'))

    expect(emitted()['update:folderName']).toBeFalsy()
  })

  it('clears folder name when selecting an existing folder', async () => {
    playlistFolderStore.state.folders = h.factory('playlist-folder', 3)
    const folders = playlistFolderStore.state.folders

    const { emitted } = h.render(Component, {
      props: {
        folderId: null,
        'onUpdate:folderId': (value: any) => value,
        folderName: 'Pending Folder',
        'onUpdate:folderName': (value: any) => value,
      },
    })

    await h.user.selectOptions(screen.getByRole('combobox'), folders[0].id)

    await waitFor(() => {
      const lastFolderNameEmit = emitted()['update:folderName'].at(-1)
      expect(lastFolderNameEmit).toEqual([null])
      const lastFolderIdEmit = emitted()['update:folderId'].at(-1)
      expect(lastFolderIdEmit).toEqual([folders[0].id])
    })
  })
})
