import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ScreenHeader.vue'

describe('screenHeader', () => {
  const h = createHarness()

  it('defaults to expanded layout', () => {
    const { container } = h.render(Component, {
      slots: { default: 'Title' },
    })

    expect(container.querySelector('header')?.classList.contains('expanded')).toBe(true)
  })
})
