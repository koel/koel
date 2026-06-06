import { describe, expect, it, vi } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { equalizerStore } from '@/stores/equalizerStore'
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
    bands: Array.from({ length: 10 }, (_, index) => ({
      label: `band-${index}`,
      db: 0,
      node: {},
    })),
    changePreampGain: vi.fn(),
    changeFilterGain: vi.fn(),
  },
}))

describe('equalizer.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      h.mock(equalizerStore, 'init')
      h.mock(equalizerStore, 'getConfig').mockReturnValue({
        id: undefined,
        name: 'Default',
        preamp: 0,
        gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
      })
    },
  })

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
