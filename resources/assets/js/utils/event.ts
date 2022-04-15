import { EventName } from '@/config'

export const eventBus = {
  all: new Map(),

  on (name: EventName | Partial<{ [K in EventName]: TAnyFunction }>, callback?: TAnyFunction) {
    if (typeof name === 'object') {
      for (const k in name) {
        this.on(k as EventName, name[k as EventName])
      }
      return
    }

    if (this.all.has(name)) {
      this.all.get(name).push(callback)
      return
    }

    this.all.set(name, [callback])
  },

  emit (name: EventName, ...args: any) {
    if (this.all.has(name)) {
      this.all.get(name).forEach((cb: TAnyFunction) => cb(...args))
    } else {
      console.warn(`Event ${name} is not listened by any component`)
    }
  }
}
