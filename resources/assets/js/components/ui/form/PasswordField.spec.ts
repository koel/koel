import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PasswordField.vue'

describe('passwordField.vue', () => {
  const h = createHarness()

  it('renders plain text', async () => {
    h.render(Component)
    const input = screen.getByTestId('input')
    const toggle = screen.getByTestId('toggle')

    await h.trigger(toggle, 'click')
    expect(input.getAttribute('type')).toBe('text')

    await h.trigger(toggle, 'click')
    expect(input.getAttribute('type')).toBe('password')
  })
})
