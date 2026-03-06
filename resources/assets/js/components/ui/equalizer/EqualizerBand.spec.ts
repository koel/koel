import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EqualizerBand.vue'

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

describe('equalizerBand.vue', () => {
  const h = createHarness()

  it('renders with label slot', () => {
    h.render(Component, {
      props: { type: 'gain', modelValue: 0 },
      slots: { default: '1K' },
    })

    screen.getByText('1K')
  })

  it('renders slider element', () => {
    const { container } = h.render(Component, {
      props: { type: 'preamp', modelValue: 5 },
      slots: { default: 'Preamp' },
    })

    expect(container.querySelector('.slider')).toBeTruthy()
  })
})
