import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { milkdropPresetLibrary } from '@/services/milkdropPresetLibrary'
import Component from './VisualizerScreen.vue'

vi.mock('@/stores/visualizerStore', () => {
  const all = [
    { id: 'default', name: 'Default Visualizer', init: vi.fn().mockResolvedValue(vi.fn()) },
    {
      id: 'projectm',
      name: 'MilkDrop Visualizer',
      importsMilkdropPresets: true,
      init: vi.fn().mockResolvedValue(vi.fn()),
    },
  ]

  return {
    visualizerStore: {
      all,
      getVisualizerById: (id: string) => all.find(visualizer => visualizer.id === id),
    },
  }
})

describe('visualizerScreen.vue', () => {
  const h = createHarness()

  it('renders visualizer selector', () => {
    h.render(Component)
    screen.getByText('Default Visualizer')
  })

  it('imports MilkDrop presets picked for a visualizer that plays them', async () => {
    const importMock = h.mock(milkdropPresetLibrary, 'importFiles').mockResolvedValue([])
    h.render(Component)

    expect(screen.queryByLabelText('Import presets')).toBeNull()

    await h.user.selectOptions(screen.getByRole('combobox'), 'projectm')

    const preset = new File(['[preset00]'], 'Aurora.milk')
    await h.user.upload(await screen.findByLabelText('Import presets'), preset)

    expect(importMock).toHaveBeenCalledWith(expect.objectContaining({ 0: preset }))
  })
})
