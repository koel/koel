import { screen } from '@testing-library/vue'
import { describe, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Btn.vue'

describe('btn.vue', () => {
  const h = createHarness()

  it('renders a button by default', () => {
    h.render(Component)

    screen.getByRole('button')
  })

  it('renders a link when asked to', () => {
    h.render(Component, {
      props: { tag: 'a' },
      attrs: { href: 'https://koel.dev' },
    })

    screen.getByRole('link')
  })
})
