import { describe, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
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
    getConfig: () => ({ name: 'Default', preamp: 0 }),
    getPresetByName: () => null,
    saveConfig: vi.fn(),
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

  it('renders preset selector and close button', () => {
    h.render(Component)

    screen.getByText('Default')
    screen.getByText('Rock')
    screen.getByText('Close')
  })
})
