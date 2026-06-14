import { screen } from '@testing-library/vue'
import { describe, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './HomeScreenBlock.vue'

describe('HomeScreenBlock', () => {
  const h = createHarness()

  it('renders header, actions, and default slots', () => {
    h.render(Component, {
      slots: {
        header: 'Recently Played',
        actions: '<button data-testid="action">act</button>',
        default: '<p>Song list</p>',
      },
    })

    screen.getByText('Recently Played')
    screen.getByTestId('action')
    screen.getByText('Song list')
  })
})
