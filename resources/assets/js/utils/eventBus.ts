import { EventName } from '@/config'
import { logger } from '@/utils'

export const eventBus = {
  all: new Map(),

  on (name: EventName | EventName[] | Partial<{ [K in EventName]: Closure }>, callback?: Closure) {
    if (Array.isArray(name)) {
      name.forEach(k => this.on(k, callback))
      return
    }

    if (typeof name === 'object') {
      for (const k in name) {
        this.on(k as EventName, name[k as EventName])
      }
      return
    }

    this.all.has(name) ? this.all.get(name).push(callback) : this.all.set(name, [callback])
  },

  emit (name: EventName, ...args: any) {
    if (this.all.has(name)) {
      this.all.get(name).forEach((cb: Closure) => cb(...args))
    } else {
      logger.warn(`Event ${name} is not listened by any component`)
    }
  }
}
