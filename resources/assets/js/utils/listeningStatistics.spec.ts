import { describe, expect, it } from 'vite-plus/test'
import { getPlaysByHour, getPlaysByWeekday, getPlaysOverTime } from './listeningStatistics'

const localIso = (year: number, month: number, day: number, hour: number) =>
  new Date(year, month - 1, day, hour).toISOString()

describe('listeningStatistics', () => {
  const now = new Date(2026, 9, 9, 15)

  it('counts plays per local day for the last week', () => {
    const bars = getPlaysOverTime(
      [
        { hour: localIso(2026, 10, 3, 9), plays: 1 },
        { hour: localIso(2026, 10, 9, 8), plays: 2 },
        { hour: localIso(2026, 10, 9, 14), plays: 3 },
      ],
      'week',
      now,
    )

    expect(bars.map(bar => bar.key)).toEqual([
      '2026-10-03',
      '2026-10-04',
      '2026-10-05',
      '2026-10-06',
      '2026-10-07',
      '2026-10-08',
      '2026-10-09',
    ])
    expect(bars.map(bar => bar.plays)).toEqual([1, 0, 0, 0, 0, 0, 5])
  })

  it('covers thirty days for the last month', () => {
    expect(getPlaysOverTime([], 'month', now)).toHaveLength(30)
  })

  it('counts plays per local month for the last year', () => {
    const bars = getPlaysOverTime([{ hour: localIso(2026, 3, 15, 10), plays: 4 }], 'year', now)

    expect(bars).toHaveLength(12)
    expect(bars[0].key).toBe('2025-11')
    expect(bars.find(bar => bar.key === '2026-03')?.plays).toBe(4)
  })

  it('starts all-time months at the first play', () => {
    const bars = getPlaysOverTime([{ hour: localIso(2026, 8, 20, 10), plays: 2 }], 'all', now)

    expect(bars.map(bar => bar.key)).toEqual(['2026-08', '2026-09', '2026-10'])
  })

  it('counts plays per local weekday starting on Monday', () => {
    const bars = getPlaysByWeekday([
      { hour: localIso(2026, 10, 5, 9), plays: 2 },
      { hour: localIso(2026, 10, 11, 9), plays: 1 },
    ])

    expect(bars.map(bar => bar.plays)).toEqual([2, 0, 0, 0, 0, 0, 1])
  })

  it('counts plays per local hour of the day', () => {
    const bars = getPlaysByHour([
      { hour: localIso(2026, 10, 5, 23), plays: 2 },
      { hour: localIso(2026, 10, 6, 23), plays: 1 },
    ])

    expect(bars).toHaveLength(24)
    expect(bars[23].plays).toBe(3)
  })
})
