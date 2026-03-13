import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './RadioStationExcerptResultsBlock.vue'

describe('radioStationExcerptResultsBlock.vue', () => {
  const h = createHarness()

  it('shows "None found." when no stations', () => {
    h.render(Component, {
      props: { stations: [], searching: false },
    })

    screen.getByText('None found.')
  })

  it('renders station cards when stations are provided', () => {
    const stations = h.factory('radio-station', 3)

    h.render(Component, {
      props: { stations, searching: false },
    })

    expect(screen.queryByText('None found.')).toBeNull()
  })
})
