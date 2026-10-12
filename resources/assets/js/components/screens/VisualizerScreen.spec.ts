import { describe, expect, it, vi } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { milkdropPresetStore, RANDOM_PRESET } from '@/stores/milkdropPresetStore'
import ManageMilkdropPresetsModal from '@/components/visualizer/ManageMilkdropPresetsModal.vue'
import Component from './VisualizerScreen.vue'

const openModalSpy = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({ openModal: openModalSpy, closeModal: vi.fn() }),
}))

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
  const h = createHarness({
    beforeEach: () => {
      milkdropPresetStore.state.names = ['Aurora', 'Nova']
      milkdropPresetStore.state.selected = RANDOM_PRESET
    },
  })

  const renderWithPresetPicker = async () => {
    h.render(Component)
    await h.user.selectOptions(screen.getAllByRole('combobox')[0], 'projectm')

    return await screen.findByRole('combobox', { name: 'Preset' })
  }

  it('renders visualizer selector', () => {
    h.render(Component)
    screen.getByText('Default Visualizer')
  })

  it('shows the preset picker only for a visualizer that plays MilkDrop presets', async () => {
    h.render(Component)
    expect(screen.queryByRole('combobox', { name: 'Preset' })).toBeNull()

    await renderWithPresetPicker()
  })

  it('selects a preset', async () => {
    const selectMock = h.mock(milkdropPresetStore, 'select')

    await h.user.selectOptions(await renderWithPresetPicker(), 'Nova')

    expect(selectMock).toHaveBeenCalledWith('Nova')
  })

  it('opens the preset manager and keeps the current choice', async () => {
    const picker = await renderWithPresetPicker()

    await h.user.selectOptions(picker, 'manage-presets')

    expect(openModalSpy).toHaveBeenCalledWith(ManageMilkdropPresetsModal)
    await waitFor(() => expect((picker as HTMLSelectElement).value).toBe(RANDOM_PRESET))
  })

  it('shows a preset the store switched to', async () => {
    const picker = await renderWithPresetPicker()

    milkdropPresetStore.state.selected = 'Aurora'

    await waitFor(() => expect((picker as HTMLSelectElement).value).toBe('Aurora'))
  })
})
