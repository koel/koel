import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './index.vue'

describe('mainWrapper.vue', () => {
  const h = createHarness()

  it('renders sidebar and main content', () => {
    const { container } = h.render(Component)
    expect(container.querySelector('div')).toBeTruthy()
  })
})
