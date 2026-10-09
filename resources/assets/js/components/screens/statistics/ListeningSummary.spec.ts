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
          previous_summary: null,
          discoveries: null,
          streak: { current_days: 2, longest_days: 4 },
          ...overrides,
        },
      },
    })

  const terms = () => screen.getAllByRole('term').map(term => term.textContent)

  const previousSummary = (plays: number): ListeningSummary => ({
    plays,
    listening_time: 2400,
    song_count: 8,
    artist_count: 5,
  })

  it.each<[ListeningSummary | null, string | null]>([
    [previousSummary(10), '+20%'],
    [previousSummary(15), '−20%'],
    [previousSummary(12), 'Same'],
    [previousSummary(0), null],
    [null, null],
  ])('compares the 12 plays with the previous period %#', (previous, expectedStart) => {
    renderComponent({ previous_summary: previous })

    const playsNote = screen.getByText('Plays').parentElement!.querySelector('[data-testid="figure-note"]')

    if (expectedStart === null) {
      expect(playsNote).toBeNull()
    } else {
      expect(playsNote?.textContent?.startsWith(expectedStart)).toBe(true)
    }
  })

  it('compares every summary figure with the previous period', () => {
    renderComponent({ previous_summary: previousSummary(10) })

    expect(screen.getAllByTestId('figure-note')).toHaveLength(4)
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
