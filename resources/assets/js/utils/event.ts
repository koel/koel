import { EventName } from '@/config'

export const eventBus = {
  all: new Map,

  on (name: EventName | Partial<{ [K in EventName]: Function }>, callback?: Function) {
    if (typeof name === 'object') {
      for (let k in name) {
        this.on(k as EventName, name[k as EventName])
      }
      return
    }

    this.all.set(name, callback)
  },

  emit (name: EventName, ...args: any): void {
    if (this.all.has(name)) {
      this.all.get(name)(...args)
    }
  }
}
