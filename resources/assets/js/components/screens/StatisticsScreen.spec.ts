import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { statisticsService } from '@/services/statisticsService'
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
    hourly_listening: plays ? [{ hour: '2026-10-09T14:00:00Z', plays, listening_time: plays * 200 }] : [],
    previous_summary: null,
    discoveries: null,
    streak: { current_days: 0, longest_days: 0 },
  })

  const renderComponent = (libraryStatistics: LibraryStatistic[] = []) => {
    h.mock(statisticsService, 'fetchLibraryStatistics').mockResolvedValue(libraryStatistics)
    h.visit('/statistics')

    return h.render(Component, {
      global: {
        stubs: {
          ListeningBarChart: h.stub('listening-bar-chart'),
        },
      },
    })
  }

  it('shows the last 30 days when opened', async () => {
    const fetchMock = h.mock(statisticsService, 'fetchListeningStatistics').mockResolvedValue(makeStatistics(3))
    renderComponent()

    await waitFor(() => screen.getByTestId('statistics'))
    expect(fetchMock).toHaveBeenCalledWith('month')
  })

  it('fetches again when the period changes', async () => {
    const fetchMock = h.mock(statisticsService, 'fetchListeningStatistics').mockResolvedValue(makeStatistics(3))
    renderComponent()

    await h.user.click(await screen.findByLabelText('12 months'))

    await waitFor(() => expect(fetchMock).toHaveBeenLastCalledWith('year'))
  })

  it('shows skeletons while loading', async () => {
    h.mock(statisticsService, 'fetchListeningStatistics').mockReturnValue(new Promise(() => {}))
    h.mock(statisticsService, 'fetchLibraryStatistics').mockReturnValue(new Promise(() => {}))
    h.visit('/statistics')
    h.render(Component, { global: { stubs: { ListeningBarChart: h.stub('listening-bar-chart') } } })

    await waitFor(() => {
      screen.getByTestId('statistics-skeleton')
      screen.getByTestId('library-statistics-skeleton')
    })
  })

  it('explains when there are no plays in the period', async () => {
    h.mock(statisticsService, 'fetchListeningStatistics').mockResolvedValue(makeStatistics(0))
    renderComponent()

    await waitFor(() => screen.getByTestId('statistics-empty'))
  })

  it('keeps the latest period when an earlier answer arrives last', async () => {
    let finishAllTime: (statistics: ListeningStatistics) => void = () => {}

    h.mock(statisticsService, 'fetchListeningStatistics').mockImplementation(async (period: string) => {
      if (period === 'all') {
        return await new Promise<ListeningStatistics>(resolve => {
          finishAllTime = resolve
        })
      }

      return makeStatistics(period === 'week' ? 0 : 3)
    })

    renderComponent()
    await screen.findByTestId('statistics')

    await h.user.click(screen.getByLabelText('All time'))
    await h.user.click(screen.getByLabelText('7 days'))
    await waitFor(() => screen.getByTestId('statistics-empty'))

    finishAllTime(makeStatistics(9))
    await h.tick(2)

    screen.getByTestId('statistics-empty')
  })

  it('shows the library statistics even without plays in the period', async () => {
    h.mock(statisticsService, 'fetchListeningStatistics').mockResolvedValue(makeStatistics(0))
    renderComponent([{ key: 'storage', label: 'Storage used', value: 40, unit: 'bytes', used: 40, limit: 100 }])

    await waitFor(() => screen.getByTestId('statistics-empty'))
    screen.getByRole('meter')
  })

  it('shows the library statistics above the period selector', async () => {
    h.mock(statisticsService, 'fetchListeningStatistics').mockResolvedValue(makeStatistics(3))
    renderComponent([{ key: 'storage', label: 'Storage used', value: 40, unit: 'bytes', used: 40, limit: 100 }])

    const meter = await screen.findByRole('meter')
    const periodOption = screen.getByLabelText('12 months')

    expect(meter.compareDocumentPosition(periodOption) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy()
  })
})
