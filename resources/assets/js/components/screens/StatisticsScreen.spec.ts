import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { listeningStatisticsService } from '@/services/listeningStatisticsService'
import Component from './StatisticsScreen.vue'

describe('statisticsScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      Element.prototype.scrollTo = vi.fn()
    },
  })

  const makeStatistics = (plays: number): ListeningStatistics => ({
    summary: { plays, listening_time: plays * 200, song_count: 1, artist_count: 1 },
    top_songs: [],
    top_artists: [],
    top_albums: [],
    top_genres: [],
    hourly_plays: plays ? [{ hour: '2026-10-09T14:00:00Z', plays }] : [],
    previous_summary: null,
    discoveries: null,
    streak: { current_days: 0, longest_days: 0 },
  })

  const renderComponent = () => {
    h.visit('/statistics')

    return h.render(Component, {
      global: {
        stubs: {
          PlaysBarChart: h.stub('plays-bar-chart'),
        },
      },
    })
  }

  it('shows the last 30 days when opened', async () => {
    const fetchMock = h.mock(listeningStatisticsService, 'fetch').mockResolvedValue(makeStatistics(3))
    renderComponent()

    await waitFor(() => screen.getByTestId('statistics'))
    expect(fetchMock).toHaveBeenCalledWith('month')
  })

  it('fetches again when the period changes', async () => {
    const fetchMock = h.mock(listeningStatisticsService, 'fetch').mockResolvedValue(makeStatistics(3))
    renderComponent()

    await h.user.click(await screen.findByLabelText('12 months'))

    await waitFor(() => expect(fetchMock).toHaveBeenLastCalledWith('year'))
  })

  it('explains when there are no plays in the period', async () => {
    h.mock(listeningStatisticsService, 'fetch').mockResolvedValue(makeStatistics(0))
    renderComponent()

    await waitFor(() => screen.getByTestId('statistics-empty'))
  })
})
