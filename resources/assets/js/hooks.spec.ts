import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { Action, Filter } from '@/config/hooks'
import { addAction, addFilter, applyFilters, doAction, removeAction, removeFilter } from '@/hooks'

describe('hooks', () => {
  createHarness()

  it('returns the value untouched when nobody filters', () => {
    expect(applyFilters(Filter.SCREENS, { kept: true })).toEqual({ kept: true })
  })

  it('hands each filter what the previous one returned', () => {
    const first = addFilter<string[]>(Filter.SCREENS, value => [...value, 'first'])
    const second = addFilter<string[]>(Filter.SCREENS, value => [...value, 'second'])

    expect(applyFilters<string[]>(Filter.SCREENS, [])).toEqual(['first', 'second'])

    removeFilter(first)
    removeFilter(second)
  })

  it('runs filters in priority order', () => {
    const late = addFilter<string>(Filter.POLICIES, value => `${value} late`, 20)
    const early = addFilter<string>(Filter.POLICIES, value => `${value} early`, 5)

    expect(applyFilters(Filter.POLICIES, 'start')).toBe('start early late')

    removeFilter(late)
    removeFilter(early)
  })

  it('passes the extra arguments to a filter', () => {
    const handle = addFilter<string>(Filter.POLICIES, (value, suffix: string) => `${value} ${suffix}`)

    expect(applyFilters(Filter.POLICIES, 'value', 'suffix')).toBe('value suffix')

    removeFilter(handle)
  })

  it('runs actions in priority order with their arguments', () => {
    const calls: string[] = []

    const late = addAction(Action.APPLICATION_CREATED, (what: string) => calls.push(`late ${what}`), 20)
    const early = addAction(Action.APPLICATION_CREATED, (what: string) => calls.push(`early ${what}`), 5)

    doAction(Action.APPLICATION_CREATED, 'created')

    expect(calls).toEqual(['early created', 'late created'])

    removeAction(late)
    removeAction(early)
  })

  it('does nothing when nobody listens to an action', () => {
    expect(() => doAction(Action.APPLICATION_CREATED)).not.toThrow()
  })

  it('removes one callback and leaves the others', () => {
    const removed = addFilter<string[]>(Filter.MANAGE_SIDEBAR_ITEMS, value => [...value, 'removed'])
    const kept = addFilter<string[]>(Filter.MANAGE_SIDEBAR_ITEMS, value => [...value, 'kept'])

    removeFilter(removed)

    expect(applyFilters<string[]>(Filter.MANAGE_SIDEBAR_ITEMS, [])).toEqual(['kept'])

    removeFilter(kept)
  })

  it('shrugs at a handle that was already removed', () => {
    const handle = addFilter<string[]>(Filter.ROUTES, value => value)

    removeFilter(handle)
    removeFilter(handle)

    expect(applyFilters(Filter.ROUTES, ['kept'])).toEqual(['kept'])
  })

  it('filters through a custom hook name', () => {
    const handle = addFilter<string[]>('plugin-payload', value => [...value, 'added'])

    expect(applyFilters<string[]>('plugin-payload', [])).toEqual(['added'])

    removeFilter(handle)
  })

  it('acts through a custom hook name', () => {
    const calls: string[] = []
    const handle = addAction('plugin-event', () => calls.push('called'))

    doAction('plugin-event')

    expect(calls).toEqual(['called'])

    removeAction(handle)
  })
})
