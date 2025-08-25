import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SidebarToggleButton.vue'

describe('sidebarToggleButton.vue', () => {
  const h = createHarness()

  it('emits the toggle event', () => {
    const { emitted } = h.render(Component)
    h.trigger(screen.getByRole('checkbox'), 'click')
    expect(emitted()['update:modelValue']).toBeTruthy()
  })
})
