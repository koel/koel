export type ListeningPeriod = 'week' | 'month' | 'year' | 'all'
export type ListeningMeasure = 'plays' | 'minutes'

export interface HourlyListening {
  hour: string
  plays: number
  listening_time: number
}

export interface ListeningBar {
  key: string
  label: string
  value: number
}

const pad = (value: number) => String(value).padStart(2, '0')
const localDayKey = (date: Date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
const localMonthKey = (date: Date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}`

const measureOf = (listening: HourlyListening, measure: ListeningMeasure) =>
  measure === 'plays' ? listening.plays : listening.listening_time / 60

const sumByKey = (hourlyListening: HourlyListening[], measure: ListeningMeasure, keyOf: (date: Date) => string) => {
  const sums = new Map<string, number>()

  hourlyListening.forEach(listening => {
    const key = keyOf(new Date(listening.hour))
    sums.set(key, (sums.get(key) ?? 0) + measureOf(listening, measure))
  })

  return sums
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

export const getListeningOverTime = (
  hourlyListening: HourlyListening[],
  period: ListeningPeriod,
  measure: ListeningMeasure,
  now = new Date(),
) => {
  if (period === 'week' || period === 'month') {
    const sums = sumByKey(hourlyListening, measure, localDayKey)
    const dayLabel = new Intl.DateTimeFormat(undefined, period === 'week' ? { weekday: 'short' } : { day: 'numeric' })

    return lastDays(period === 'week' ? 7 : 30, now).map<ListeningBar>(day => ({
      key: localDayKey(day),
      label: dayLabel.format(day),
      value: sums.get(localDayKey(day)) ?? 0,
    }))
  }

  const sums = sumByKey(hourlyListening, measure, localMonthKey)
  const firstPlayedAt = hourlyListening.length ? new Date(hourlyListening[0].hour) : now
  const firstMonth = period === 'year' ? new Date(now.getFullYear(), now.getMonth() - 11, 1) : firstPlayedAt
  const monthLabel = new Intl.DateTimeFormat(
    undefined,
    period === 'year' ? { month: 'short' } : { month: 'short', year: '2-digit' },
  )

  return monthsBetween(firstMonth, now).map<ListeningBar>(month => ({
    key: localMonthKey(month),
    label: monthLabel.format(month),
    value: sums.get(localMonthKey(month)) ?? 0,
  }))
}

export const getListeningByWeekday = (hourlyListening: HourlyListening[], measure: ListeningMeasure) => {
  const valuesFromMonday = Array.from({ length: 7 }, () => 0)

  hourlyListening.forEach(listening => {
    valuesFromMonday[(new Date(listening.hour).getDay() + 6) % 7] += measureOf(listening, measure)
  })

  const weekdayLabel = new Intl.DateTimeFormat(undefined, { weekday: 'short' })
  const mondayJanuaryFirst2024 = new Date(2024, 0, 1)

  return valuesFromMonday.map<ListeningBar>((value, i) => ({
    key: `weekday-${i}`,
    label: weekdayLabel.format(new Date(2024, 0, mondayJanuaryFirst2024.getDate() + i)),
    value,
  }))
}

export const getListeningByHour = (hourlyListening: HourlyListening[], measure: ListeningMeasure) => {
  const valuesByHour = Array.from({ length: 24 }, () => 0)

  hourlyListening.forEach(listening => {
    valuesByHour[new Date(listening.hour).getHours()] += measureOf(listening, measure)
  })

  return valuesByHour.map<ListeningBar>((value, hour) => ({ key: `hour-${hour}`, label: String(hour), value }))
}
