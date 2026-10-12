import { screen } from '@testing-library/vue'
import { describe, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AuthFormCard.vue'

describe('authFormCard.vue', () => {
  const h = createHarness()

  it('renders the logo and the slotted body', () => {
    h.render(Component, { slots: { default: '<p>Body content</p>' } })

    screen.getByAltText('Logo')
    screen.getByText('Body content')
  })
})
