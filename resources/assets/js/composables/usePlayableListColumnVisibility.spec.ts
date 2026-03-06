import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'

const { mockGet, mockSet } = vi.hoisted(() => ({
  mockGet: vi.fn(),
  mockSet: vi.fn(),
}))

vi.mock('@/composables/useLocalStorage', () => ({
  useLocalStorage: () => ({
    get: mockGet,
    set: mockSet,
  }),
}))

vi.mock('@vueuse/core', async importOriginal => {
  const actual = await importOriginal<Record<string, unknown>>()
  return {
    ...actual,
    useBreakpoints: () => ({
      isGreaterOrEqual: () => true,
    }),
  }
})

import { usePlayableListColumnVisibility } from './usePlayableListColumnVisibility'

describe('usePlayableListColumnVisibility', () => {
  createHarness()

  it('shows columns from local storage', () => {
    mockGet.mockReturnValue(['track', 'title', 'artist'])

    const { shouldShowColumn } = usePlayableListColumnVisibility()
    expect(shouldShowColumn('track')).toBe(true)
    expect(shouldShowColumn('title')).toBe(true)
  })

  it('always includes title column', () => {
    mockGet.mockReturnValue(['track'])

    const { shouldShowColumn } = usePlayableListColumnVisibility()
    expect(shouldShowColumn('title')).toBe(true)
  })

  it('toggles a column on', () => {
    mockGet.mockReturnValue(['track', 'title'])

    const { toggleColumn, shouldShowColumn } = usePlayableListColumnVisibility()
    toggleColumn('genre')
    expect(shouldShowColumn('genre')).toBe(true)
    expect(mockSet).toHaveBeenCalled()
  })

  it('toggles a column off', () => {
    mockGet.mockReturnValue(['track', 'title', 'genre'])

    const { toggleColumn } = usePlayableListColumnVisibility()
    toggleColumn('genre')
    expect(mockSet).toHaveBeenCalled()
  })
})
