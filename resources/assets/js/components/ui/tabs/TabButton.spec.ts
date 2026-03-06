import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './TabButton.vue'

describe('tabButton.vue', () => {
  const h = createHarness()

  it('renders with aria-selected when selected', () => {
    const { getByRole } = h.render(Component, {
      props: { selected: true },
      slots: { default: 'Tab 1' },
    })

    expect(getByRole('tab').getAttribute('aria-selected')).toBe('true')
  })

  it('renders with aria-selected false when not selected', () => {
    const { getByRole } = h.render(Component, {
      props: { selected: false },
      slots: { default: 'Tab 1' },
    })

    expect(getByRole('tab').getAttribute('aria-selected')).toBe('false')
  })

  it('emits click on click', async () => {
    const { getByRole, emitted } = h.render(Component, {
      props: { selected: false },
      slots: { default: 'Tab 1' },
    })

    await h.user.click(getByRole('tab'))
    expect(emitted().click).toBeTruthy()
  })
})
