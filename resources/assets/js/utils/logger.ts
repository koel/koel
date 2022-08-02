const prefix = `[koel]`

export const logger = {
  warn: (m, ...args: any[]) => process.env.NODE_ENV === 'development' && console.warn(prefix, m, ...args),
  log: (m, ...args: any[]) => console.log(prefix, m, ...args),
  error: (m, ...args: any[]) => console.error(prefix, m, ...args),
  info: (m, ...args: any[]) => console.info(prefix, m, ...args)
}
