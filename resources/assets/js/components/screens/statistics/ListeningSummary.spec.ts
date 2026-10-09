import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ListeningSummary.vue'

describe('listeningSummary.vue', () => {
  const h = createHarness()

  const renderComponent = (overrides: Partial<ListeningStatistics> = {}) =>
    h.render(Component, {
      props: {
        periodLabel: '7 days',
        statistics: {
          summary: { plays: 12, listening_time: 2400, song_count: 8, artist_count: 5 },
          top_songs: [],
          top_artists: [],
          top_albums: [],
          top_genres: [],
          hourly_plays: [],
          previous_plays: null,
          discoveries: null,
          streak: { current_days: 2, longest_days: 4 },
          ...overrides,
        },
      },
    })

  const terms = () => screen.getAllByRole('term').map(term => term.textContent)

  it.each<[number | null, string | null]>([
    [10, '+20%'],
    [15, '−20%'],
    [12, 'Same'],
    [null, null],
    [0, null],
  ])('compares %s previous plays with the current 12', (previousPlays, expectedStart) => {
    renderComponent({ previous_plays: previousPlays })

    const note = screen.queryByTestId('figure-note')

    if (expectedStart === null) {
      expect(note).toBeNull()
    } else {
      expect(note?.textContent?.startsWith(expectedStart)).toBe(true)
    }
  })

  it('shows discoveries when the period has them', () => {
    renderComponent({ discoveries: { song_count: 3, artist_count: 1 } })

    expect(terms()).toHaveLength(8)
  })

  it('leaves out discoveries for all time', () => {
    renderComponent()

    expect(terms()).toHaveLength(6)
  })
})
