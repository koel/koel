const prefix = `[koel]`

export const logger = {
  warn: (m: any, ...args: any[]) => process.env.NODE_ENV === 'development' && console.warn(prefix, m, ...args),
  log: (m: any, ...args: any[]) => console.log(prefix, m, ...args),
  error: (m: any, ...args: any[]) => console.error(prefix, m, ...args),
  info: (m: any, ...args: any[]) => console.info(prefix, m, ...args)
}
