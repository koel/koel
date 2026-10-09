export type ListeningPeriod = 'week' | 'month' | 'year' | 'all'

export interface HourlyPlays {
  hour: string
  plays: number
}

export interface PlaysBar {
  key: string
  label: string
  plays: number
}

const pad = (value: number) => String(value).padStart(2, '0')
const localDayKey = (date: Date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
const localMonthKey = (date: Date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}`

const countByKey = (hourlyPlays: HourlyPlays[], keyOf: (date: Date) => string) => {
  const counts = new Map<string, number>()

  hourlyPlays.forEach(({ hour, plays }) => {
    const key = keyOf(new Date(hour))
    counts.set(key, (counts.get(key) ?? 0) + plays)
  })

  return counts
}

const lastDays = (count: number, today: Date) =>
  Array.from(
    { length: count },
    (_, i) => new Date(today.getFullYear(), today.getMonth(), today.getDate() - (count - 1 - i)),
  )

const monthsBetween = (first: Date, last: Date) => {
  const months: Date[] = []

  for (
    let month = new Date(first.getFullYear(), first.getMonth(), 1);
    month <= last;
    month = new Date(month.getFullYear(), month.getMonth() + 1, 1)
  ) {
    months.push(month)
  }

  return months
}

export const getPlaysOverTime = (hourlyPlays: HourlyPlays[], period: ListeningPeriod, now = new Date()) => {
  if (period === 'week' || period === 'month') {
    const counts = countByKey(hourlyPlays, localDayKey)
    const dayLabel = new Intl.DateTimeFormat(undefined, period === 'week' ? { weekday: 'short' } : { day: 'numeric' })

    return lastDays(period === 'week' ? 7 : 30, now).map<PlaysBar>(day => ({
      key: localDayKey(day),
      label: dayLabel.format(day),
      plays: counts.get(localDayKey(day)) ?? 0,
    }))
  }

  const counts = countByKey(hourlyPlays, localMonthKey)
  const firstPlayedAt = hourlyPlays.length ? new Date(hourlyPlays[0].hour) : now
  const firstMonth = period === 'year' ? new Date(now.getFullYear(), now.getMonth() - 11, 1) : firstPlayedAt
  const monthLabel = new Intl.DateTimeFormat(
    undefined,
    period === 'year' ? { month: 'short' } : { month: 'short', year: '2-digit' },
  )

  return monthsBetween(firstMonth, now).map<PlaysBar>(month => ({
    key: localMonthKey(month),
    label: monthLabel.format(month),
    plays: counts.get(localMonthKey(month)) ?? 0,
  }))
}

export const getPlaysByWeekday = (hourlyPlays: HourlyPlays[]) => {
  const playsFromMonday = Array.from({ length: 7 }, () => 0)

  hourlyPlays.forEach(({ hour, plays }) => {
    playsFromMonday[(new Date(hour).getDay() + 6) % 7] += plays
  })

  const weekdayLabel = new Intl.DateTimeFormat(undefined, { weekday: 'short' })
  const mondayJanuaryFirst2024 = new Date(2024, 0, 1)

  return playsFromMonday.map<PlaysBar>((plays, i) => ({
    key: `weekday-${i}`,
    label: weekdayLabel.format(new Date(2024, 0, mondayJanuaryFirst2024.getDate() + i)),
    plays,
  }))
}

export const getPlaysByHour = (hourlyPlays: HourlyPlays[]) => {
  const playsByHour = Array.from({ length: 24 }, () => 0)

  hourlyPlays.forEach(({ hour, plays }) => {
    playsByHour[new Date(hour).getHours()] += plays
  })

  return playsByHour.map<PlaysBar>((plays, hour) => ({ key: `hour-${hour}`, label: String(hour), plays }))
}
