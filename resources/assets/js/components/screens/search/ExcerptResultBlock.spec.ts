import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ExcerptResultBlock.vue'

describe('ExcerptResultBlock', () => {
  const h = createHarness()

  it('renders header and default slots', () => {
    h.render(Component, {
      slots: {
        header: 'Top Results',
        default: '<p>Result items</p>',
      },
    })
    expect(screen.getByText('Top Results')).toBeTruthy()
    expect(screen.getByText('Result items')).toBeTruthy()
  })
})
