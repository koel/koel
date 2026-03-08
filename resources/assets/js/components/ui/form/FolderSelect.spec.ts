import { describe, expect, it } from 'vitest'
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
        modelValue: folderId,
        'onUpdate:modelValue': (value: any) => value,
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
      expect(screen.getByPlaceholderText('Folder name')).toBeTruthy()
      expect(screen.getByTitle('Create')).toBeTruthy()
      expect(screen.getByTitle('Cancel')).toBeTruthy()
    })
  })

  it('creates a folder and selects it on submit', async () => {
    const newFolder = { ...h.factory('playlist-folder'), id: 999, name: 'Brand New' }
    const storeMock = h.mock(playlistFolderStore, 'store').mockResolvedValue(newFolder)

    const { emitted } = renderComponent()

    await h.user.selectOptions(screen.getByRole('combobox'), '__new__')

    await waitFor(() => screen.getByPlaceholderText('Folder name'))
    await h.user.type(screen.getByPlaceholderText('Folder name'), 'Brand New')
    await h.user.click(screen.getByTitle('Create'))

    await waitFor(() => {
      expect(storeMock).toHaveBeenCalledWith('Brand New')
      expect(emitted()['update:modelValue']).toBeTruthy()
      const lastEmit = emitted()['update:modelValue'].at(-1)
      expect(lastEmit).toEqual([999])
    })
  })

  it('reverts to dropdown on cancel', async () => {
    renderComponent()

    await h.user.selectOptions(screen.getByRole('combobox'), '__new__')

    await waitFor(() => screen.getByPlaceholderText('Folder name'))
    await h.user.click(screen.getByTitle('Cancel'))

    await waitFor(() => {
      expect(screen.getByRole('combobox')).toBeTruthy()
    })
  })

  it('does not submit when folder name is empty', async () => {
    const storeMock = h.mock(playlistFolderStore, 'store')

    renderComponent()

    await h.user.selectOptions(screen.getByRole('combobox'), '__new__')

    await waitFor(() => screen.getByPlaceholderText('Folder name'))
    await h.user.click(screen.getByTitle('Create'))

    expect(storeMock).not.toHaveBeenCalled()
  })
})
