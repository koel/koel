import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EqualizerBands.vue'

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
    bands: [],
    changePreampGain: vi.fn(),
    changeFilterGain: vi.fn(),
  },
}))

const bands = [
  { label: '60', db: 0, node: {} },
  { label: '170', db: 0, node: {} },
  { label: '1K', db: 0, node: {} },
]

describe('equalizerBands.vue', () => {
  const h = createHarness()

  it('renders the preamp slider, scale legend, and one slider per band', () => {
    const { container } = h.render(Component, { props: { bands } })

    screen.getByText('Preamp')
    screen.getByText('60')
    screen.getByText('170')
    screen.getByText('1K')
    screen.getByText('+20')
    screen.getByText('-20')

    expect(container.querySelectorAll('.slider')).toHaveLength(bands.length + 1)
  })
})
