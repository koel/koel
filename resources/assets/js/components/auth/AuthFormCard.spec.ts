import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AuthFormCard.vue'

describe('authFormCard.vue', () => {
  const h = createHarness()

  it('renders the logo and the slotted body', () => {
    h.render(Component, { slots: { default: '<p>Body content</p>' } })

    screen.getByAltText('Logo')
    screen.getByText('Body content')
  })

  it('applies the error class when failed', () => {
    const { container } = h.render(Component, { props: { failed: true } })

    expect(container.querySelector('form')!.classList.contains('error')).toBe(true)
  })

  it('does not apply the error class by default', () => {
    const { container } = h.render(Component)

    expect(container.querySelector('form')!.classList.contains('error')).toBe(false)
  })

  it('emits submit when the form is submitted', async () => {
    const { emitted } = h.render(Component, {
      slots: { default: '<button type="submit">Go</button>' },
    })

    await h.user.click(screen.getByText('Go'))

    expect(emitted().submit).toBeTruthy()
  })
})
