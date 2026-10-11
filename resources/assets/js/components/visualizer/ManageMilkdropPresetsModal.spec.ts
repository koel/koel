import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { milkdropPresetLibrary } from '@/services/milkdropPresetLibrary'
import { milkdropPresetStore } from '@/stores/milkdropPresetStore'
import Component from './ManageMilkdropPresetsModal.vue'

describe('manageMilkdropPresetsModal.vue', () => {
  const h = createHarness({
    beforeEach: () => (milkdropPresetStore.state.names = ['Aurora', 'Nova Burst', 'Orbit']),
  })

  it('filters presets by name', async () => {
    h.render(Component)

    await h.type(screen.getByRole('searchbox', { name: 'Search presets' }), 'nva brst')

    expect(screen.getAllByRole('listitem')).toHaveLength(1)
  })

  it('imports picked preset files', async () => {
    const importMock = h
      .mock(milkdropPresetStore, 'importFiles')
      .mockResolvedValue({ importedNames: ['Zenith'], skippedCount: 0 })

    h.render(Component)

    const preset = new File(['[preset00]'], 'Zenith.milk')
    await h.user.upload(screen.getByLabelText('Preset files'), preset)

    expect(importMock).toHaveBeenCalledWith(expect.objectContaining({ 0: preset }))
  })

  it('deletes a preset', async () => {
    const removeMock = h.mock(milkdropPresetStore, 'remove')
    h.render(Component)

    await h.user.click(screen.getAllByRole('button', { name: 'Delete' })[1])

    expect(removeMock).toHaveBeenCalledWith('Nova Burst')
  })

  it('applies a preset', async () => {
    const selectMock = h.mock(milkdropPresetStore, 'select')
    h.render(Component)

    await h.user.click(screen.getAllByRole('button', { name: 'Apply' })[2])

    expect(selectMock).toHaveBeenCalledWith('Orbit')
  })

  it('cannot apply the preset that is already applied', () => {
    milkdropPresetStore.state.selected = 'Nova Burst'
    h.render(Component)

    const [aurora, novaBurst] = screen.getAllByRole('button', { name: 'Apply' })

    expect(aurora.hasAttribute('disabled')).toBe(false)
    expect(novaBurst.hasAttribute('disabled')).toBe(true)
  })

  it('drops deleted presets from the list', async () => {
    h.mock(milkdropPresetLibrary, 'remove')
    h.mock(milkdropPresetLibrary, 'getNames')
      .mockResolvedValueOnce(['Aurora', 'Orbit'])
      .mockResolvedValueOnce(['Orbit'])

    h.render(Component)

    await h.user.click(screen.getAllByRole('button', { name: 'Delete' })[1])
    await waitFor(() => expect(screen.getAllByRole('listitem')).toHaveLength(2))

    await h.user.click(screen.getAllByRole('button', { name: 'Delete' })[0])
    await waitFor(() => expect(screen.getAllByRole('listitem')).toHaveLength(1))
  })

  it('hides the search box until there are presets', () => {
    milkdropPresetStore.state.names = []
    h.render(Component)

    expect(screen.queryByRole('searchbox', { name: 'Search presets' })).toBeNull()
  })
})
