export const limitBy = <T> (arr: T[], n: number, offset: number = 0): T[] => arr.slice(offset, offset + n)

export const pluralize = (count: number, singular: string): string =>
  count === 1 ? `${count} ${singular}` : `${count.toLocaleString()} ${singular}s`
