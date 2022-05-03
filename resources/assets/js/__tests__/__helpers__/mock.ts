import { vi } from 'vitest'
import { noop } from '@/utils'

declare type Procedure = (...args: any[]) => any;
declare type Methods<T> = { [K in keyof T]: T[K] extends Procedure ? K : never; }[keyof T] & (string | symbol);

export const mockHelper = {
  backup: new Map(),

  mock<T, M extends Methods<Required<T>>> (
    obj: T,
    methodName: M,
    implementation: any = noop
  ) {
    const m = vi.fn().mockImplementation(implementation instanceof Function ? implementation : () => implementation)
    this.backup.set([obj, methodName], obj[methodName])

    // @ts-ignore
    obj[methodName] = m

    return m
  },

  restoreMocks () {
    this.backup.forEach((fn, [obj, methodName]) => (obj[methodName] = fn))
    this.backup = new Map()
  }
}
