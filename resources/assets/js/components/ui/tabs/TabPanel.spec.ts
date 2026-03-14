import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './TabPanel.vue'

describe('tabPanel.vue', () => {
  const h = createHarness()

  it('renders slot content with tabpanel role', () => {
    const { getByRole } = h.render(Component, {
      slots: { default: 'Panel content' },
    })

    expect(getByRole('tabpanel').textContent).toBe('Panel content')
  })
})
