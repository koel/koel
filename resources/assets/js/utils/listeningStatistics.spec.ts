import { describe, expect, it } from 'vite-plus/test'
import { getListeningByHour, getListeningByWeekday, getListeningOverTime } from './listeningStatistics'

const localIso = (year: number, month: number, day: number, hour: number) =>
  new Date(year, month - 1, day, hour).toISOString()

const listening = (hour: string, plays: number, minutes = plays * 3) => ({
  hour,
  plays,
  listening_time: minutes * 60,
})

describe('listeningStatistics', () => {
  const now = new Date(2026, 9, 9, 15)

  it('sums plays per local day for the last week', () => {
    const bars = getListeningOverTime(
      [
        listening(localIso(2026, 10, 3, 9), 1),
        listening(localIso(2026, 10, 9, 8), 2),
        listening(localIso(2026, 10, 9, 14), 3),
      ],
      'week',
      'plays',
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
    expect(bars.map(bar => bar.value)).toEqual([1, 0, 0, 0, 0, 0, 5])
  })

  it('sums minutes instead of plays when asked', () => {
    const bars = getListeningOverTime([listening(localIso(2026, 10, 9, 8), 2, 7)], 'week', 'minutes', now)

    expect(bars[6].value).toBe(7)
  })

  it('covers thirty days for the last month', () => {
    expect(getListeningOverTime([], 'month', 'plays', now)).toHaveLength(30)
  })

  it('sums per local month for the last year', () => {
    const bars = getListeningOverTime([listening(localIso(2026, 3, 15, 10), 4)], 'year', 'plays', now)

    expect(bars).toHaveLength(12)
    expect(bars[0].key).toBe('2025-11')
    expect(bars.find(bar => bar.key === '2026-03')?.value).toBe(4)
  })

  it('starts all-time months at the first play', () => {
    const bars = getListeningOverTime([listening(localIso(2026, 8, 20, 10), 2)], 'all', 'plays', now)

    expect(bars.map(bar => bar.key)).toEqual(['2026-08', '2026-09', '2026-10'])
  })

  it('sums per local weekday starting on Monday', () => {
    const bars = getListeningByWeekday(
      [listening(localIso(2026, 10, 5, 9), 2), listening(localIso(2026, 10, 11, 9), 1)],
      'plays',
    )

    expect(bars.map(bar => bar.value)).toEqual([2, 0, 0, 0, 0, 0, 1])
  })

  it('sums per local hour of the day', () => {
    const bars = getListeningByHour(
      [listening(localIso(2026, 10, 5, 23), 2), listening(localIso(2026, 10, 6, 23), 1)],
      'plays',
    )

    expect(bars).toHaveLength(24)
    expect(bars[23].value).toBe(3)
  })
})
