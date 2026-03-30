import { screen } from '@testing-library/vue'
import { describe, it } from 'vite-plus/test'
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
    screen.getByText('Top Results')
    screen.getByText('Result items')
  })
})
