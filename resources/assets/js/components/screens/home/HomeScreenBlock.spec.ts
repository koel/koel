import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './HomeScreenBlock.vue'

describe('HomeScreenBlock', () => {
  const h = createHarness()

  it('renders header and default slots', () => {
    h.render(Component, {
      slots: {
        header: 'Recently Played',
        default: '<p>Song list</p>',
      },
    })
    expect(screen.getByText('Recently Played')).toBeTruthy()
    expect(screen.getByText('Song list')).toBeTruthy()
  })
})
