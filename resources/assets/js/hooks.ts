import type { Component } from 'vue'
import { Filter } from '@/config/hooks'
import type { ActionName, FilterName } from '@/config/hooks'

type HookName<KnownName extends string> = KnownName | (string & {})

export interface HookHandle {
  hook: string
  id: string
}

type Callback = (...args: any[]) => any

const DEFAULT_PRIORITY = 10

const actions: Record<string, Record<number, Record<string, Callback>>> = Object.create(null)
const filters: Record<string, Record<number, Record<string, Callback>>> = Object.create(null)

const register = (
  registry: Record<string, Record<number, Record<string, Callback>>>,
  hook: string,
  callback: Callback,
  priority: number,
): HookHandle => {
  const id = crypto.randomUUID()

  registry[hook] ??= Object.create(null)
  registry[hook][priority] ??= Object.create(null)
  registry[hook][priority][id] = callback

  return { hook, id }
}

const sortedPriorities = (buckets: Record<number, Record<string, Callback>> = {}) =>
  Object.keys(buckets)
    .map(Number)
    .sort((one, other) => one - other)

export const addAction = (action: HookName<ActionName>, callback: Callback, priority = DEFAULT_PRIORITY) =>
  register(actions, action, callback, priority)

export const doAction = (action: HookName<ActionName>, ...args: any[]) => {
  sortedPriorities(actions[action]).forEach(priority => {
    Object.values(actions[action][priority]).forEach(callback => callback(...args))
  })
}

export const addFilter = <T>(
  filter: HookName<FilterName>,
  callback: (value: T, ...args: any[]) => T,
  priority = DEFAULT_PRIORITY,
) => register(filters, filter, callback as Callback, priority)

export const addToHookSlot = (name: HookSlotName, component: Component, priority = DEFAULT_PRIORITY) =>
  addFilter<Component[]>(
    Filter.SLOT,
    (components, slotName: HookSlotName) => (slotName === name ? [...components, component] : components),
    priority,
  )

export const applyFilters = <T>(filter: HookName<FilterName>, value: T, ...args: any[]): T => {
  sortedPriorities(filters[filter]).forEach(priority => {
    Object.values(filters[filter][priority]).forEach(callback => (value = callback(value, ...args)))
  })

  return value
}

const remove = (registry: Record<string, Record<number, Record<string, Callback>>>, handle: HookHandle) =>
  sortedPriorities(registry[handle.hook]).forEach(priority => delete registry[handle.hook][priority][handle.id])

export const removeAction = (handle: HookHandle) => remove(actions, handle)

export const removeFilter = (handle: HookHandle) => remove(filters, handle)
