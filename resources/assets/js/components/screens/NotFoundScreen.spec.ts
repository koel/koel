import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './NotFoundScreen.vue'

describe('NotFoundScreen', () => {
  const h = createHarness()

  it('renders the not found message', () => {
    h.render(Component)
    expect(screen.getByText('The requested content cannot be found.')).toBeTruthy()
  })
})
