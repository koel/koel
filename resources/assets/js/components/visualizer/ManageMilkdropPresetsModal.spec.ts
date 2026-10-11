import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { milkdropPresetStore } from '@/stores/milkdropPresetStore'
import Component from './ManageMilkdropPresetsModal.vue'

describe('manageMilkdropPresetsModal.vue', () => {
  const h = createHarness({
    beforeEach: () => (milkdropPresetStore.state.names = ['Aurora', 'Nova Burst', 'Orbit']),
  })

  it('filters presets by name', async () => {
    h.render(Component)

    await h.type(screen.getByRole('searchbox', { name: 'Search presets' }), 'nova')

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

  it('plays a preset when it is clicked', async () => {
    const selectMock = h.mock(milkdropPresetStore, 'select')
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Orbit' }))

    expect(selectMock).toHaveBeenCalledWith('Orbit')
  })
})
