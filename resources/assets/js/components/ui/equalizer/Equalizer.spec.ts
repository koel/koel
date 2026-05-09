import { describe, expect, it, vi } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Equalizer.vue'

vi.mock('nouislider', () => ({
  default: {
    create: vi.fn((el: any) => {
      el.noUiSlider = {
        on: vi.fn(),
        set: vi.fn(),
      }
    }),
  },
}))

vi.mock('@/services/audioService', () => ({
  audioService: {
    bands: [
      { label: '60', db: 0, node: {} },
      { label: '170', db: 0, node: {} },
      { label: '1K', db: 0, node: {} },
    ],
    changePreampGain: vi.fn(),
    changeFilterGain: vi.fn(),
  },
}))

vi.mock('@/stores/equalizerStore', () => ({
  equalizerStore: {
    state: { customPresets: [] },
    init: vi.fn(),
    getConfig: () => ({ id: undefined, name: 'Default', preamp: 0, gains: [0, 0, 0] }),
    getBuiltInPresetByName: (name: string) =>
      [
        { name: 'Default', preamp: 0, gains: [0, 0, 0] },
        { name: 'Rock', preamp: 5, gains: [5, 3, 1] },
      ].find(p => p.name === name) ?? null,
    getCustomPresetById: () => null,
    saveConfig: vi.fn(),
    saveCustomPreset: vi.fn(),
    deleteCustomPreset: vi.fn(),
  },
}))

vi.mock('@/config/audio', () => ({
  equalizerPresets: [
    { name: 'Default', preamp: 0, gains: [0, 0, 0] },
    { name: 'Rock', preamp: 5, gains: [5, 3, 1] },
  ],
}))

describe('equalizer.vue', () => {
  const h = createHarness()

  it('wires header (preset dropdown) and bands (sliders) together', () => {
    const { container } = h.render(Component)

    screen.getByText('Default')
    screen.getByText('Rock')
    screen.getByText('Preamp')
    screen.getByText('Close')
    expect(container.querySelectorAll('.slider').length).toBeGreaterThan(0)
  })

  it('emits close when the Close button is clicked', async () => {
    const { emitted } = h.render(Component)

    await fireEvent.click(screen.getByText('Close'))

    expect(emitted().close).toHaveLength(1)
  })
})
