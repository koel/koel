import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './TabList.vue'

describe('tabList.vue', () => {
  const h = createHarness()

  it('renders slot content with tablist role', () => {
    const { getByRole } = h.render(Component, {
      slots: { default: 'Tab items' },
    })

    expect(getByRole('tablist').textContent).toBe('Tab items')
  })
})
