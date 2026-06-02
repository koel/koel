import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

vi.mock('@/composables/useLocalStorage', () => {
  const store = new Map<string, unknown>()
  return {
    useLocalStorage: () => ({
      get: <T>(key: string, fallback: T) => (store.has(key) ? (store.get(key) as T) : fallback),
      set: (key: string, value: unknown) => store.set(key, value),
      remove: (key: string) => store.delete(key),
    }),
  }
})

import { useTableColumnVisibility } from './useTableColumnVisibility'

type Col = 'name' | 'artist' | 'time' | 'year' | 'rating' | 'favorite'

const buildConfig = (storageKey: string) =>
  ({
    storageKey,
    validColumns: ['name', 'artist', 'time', 'year', 'rating', 'favorite'] as const,
    defaultColumns: ['name', 'artist', 'rating', 'favorite'] as const,
    alwaysVisible: ['name'] as const,
  }) satisfies {
    storageKey: string
    validColumns: readonly Col[]
    defaultColumns: readonly Col[]
    alwaysVisible: readonly Col[]
  }

describe('useTableColumnVisibility', () => {
  createHarness()

  it('shows the default columns when nothing is stored', () => {
    const { shouldShowColumn } = useTableColumnVisibility(buildConfig('test-defaults'))

    expect(shouldShowColumn('name')).toBe(true)
    expect(shouldShowColumn('artist')).toBe(true)
    expect(shouldShowColumn('rating')).toBe(true)
    expect(shouldShowColumn('favorite')).toBe(true)
    expect(shouldShowColumn('time')).toBe(false)
    expect(shouldShowColumn('year')).toBe(false)
  })

  it('toggling a column flips its visibility', () => {
    const { shouldShowColumn, toggleColumn } = useTableColumnVisibility(buildConfig('test-toggle'))

    expect(shouldShowColumn('time')).toBe(false)
    toggleColumn('time')
    expect(shouldShowColumn('time')).toBe(true)
    toggleColumn('time')
    expect(shouldShowColumn('time')).toBe(false)
  })

  it('refuses to toggle an alwaysVisible column off', () => {
    const { shouldShowColumn, toggleColumn, isToggleable } = useTableColumnVisibility(buildConfig('test-locked'))

    expect(isToggleable('name')).toBe(false)
    expect(shouldShowColumn('name')).toBe(true)

    toggleColumn('name')

    expect(shouldShowColumn('name')).toBe(true)
  })

  it('marks toggleable columns correctly', () => {
    const { isToggleable } = useTableColumnVisibility(buildConfig('test-toggleable'))

    expect(isToggleable('name')).toBe(false)
    expect(isToggleable('artist')).toBe(true)
    expect(isToggleable('rating')).toBe(true)
    expect(isToggleable('favorite')).toBe(true)
  })
})
