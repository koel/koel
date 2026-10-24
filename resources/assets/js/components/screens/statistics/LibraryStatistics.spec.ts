import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './LibraryStatistics.vue'

describe('libraryStatistics.vue', () => {
  const h = createHarness()

  const makeStatistic = (overrides: Partial<LibraryStatistic> = {}): LibraryStatistic => ({
    key: 'songs',
    label: 'Songs',
    value: 1200,
    unit: 'count',
    used: null,
    limit: null,
    ...overrides,
  })

  it('shows every statistic in the given order', () => {
    h.render(Component, {
      props: {
        statistics: [
          makeStatistic({ key: 'songs', label: 'Songs' }),
          makeStatistic({ key: 'albums', label: 'Albums' }),
          makeStatistic({ key: 'storage', label: 'Storage used', unit: 'bytes' }),
        ],
      },
    })

    expect(screen.getAllByRole('term').map(term => term.textContent)).toEqual(['Songs', 'Albums', 'Storage used'])
  })

  it('measures a statistic against its limit', () => {
    h.render(Component, {
      props: {
        statistics: [
          makeStatistic(),
          makeStatistic({ key: 'storage', unit: 'bytes', value: 40, used: 30, limit: 100 }),
        ],
      },
    })

    const meter = screen.getByRole('meter')

    expect([meter.getAttribute('aria-valuenow'), meter.getAttribute('aria-valuemax')]).toEqual(['30', '100'])
  })
})
