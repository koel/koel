import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useVault } from './useVault'

interface Item {
  id: string
  name: string
  meta?: { genre?: string; year?: number }
}

describe('useVault', () => {
  createHarness()

  it('returns undefined when looking up a missing id', () => {
    const vault = useVault<Item>()
    expect(vault.byId('nope')).toBeUndefined()
  })

  it('inserts new items via syncWithVault and looks them up by id', () => {
    const vault = useVault<Item>()

    const [synced] = vault.syncWithVault({ id: 'a', name: 'Alpha' })

    expect(vault.byId('a')).toBe(synced)
    expect(vault.byId('a')?.name).toBe('Alpha')
  })

  it('deep-merges updates into existing items, preserving identity', () => {
    const vault = useVault<Item>()

    const [first] = vault.syncWithVault({ id: 'a', name: 'Alpha', meta: { genre: 'rock' } })
    const [second] = vault.syncWithVault({ id: 'a', name: 'Alpha v2', meta: { year: 1969 } })

    expect(second).toBe(first)
    expect(second.name).toBe('Alpha v2')
    expect(second.meta).toEqual({ genre: 'rock', year: 1969 })
  })

  it('accepts a single item or an array', () => {
    const vault = useVault<Item>()

    const single = vault.syncWithVault({ id: 'a', name: 'Alpha' })
    const many = vault.syncWithVault([
      { id: 'b', name: 'Bravo' },
      { id: 'c', name: 'Charlie' },
    ])

    expect(single).toHaveLength(1)
    expect(many).toHaveLength(2)
    expect(vault.byId('b')?.name).toBe('Bravo')
    expect(vault.byId('c')?.name).toBe('Charlie')
  })

  it('exposes the underlying Map for direct operations', () => {
    const vault = useVault<Item>()

    vault.syncWithVault([
      { id: 'a', name: 'Alpha' },
      { id: 'b', name: 'Bravo' },
    ])

    expect(vault.vault.size).toBe(2)
    vault.vault.delete('a')
    expect(vault.byId('a')).toBeUndefined()
    expect(vault.byId('b')?.name).toBe('Bravo')
  })

  it('isolates instances from one another', () => {
    const a = useVault<Item>()
    const b = useVault<Item>()

    a.syncWithVault({ id: 'x', name: 'in A' })

    expect(a.byId('x')?.name).toBe('in A')
    expect(b.byId('x')).toBeUndefined()
  })

  it('runs onItemAdded exactly once for newly added entries and not for updates', () => {
    const onItemAdded = vi.fn()
    const vault = useVault<Item>({ onItemAdded })

    vault.syncWithVault({ id: 'a', name: 'Alpha' })
    vault.syncWithVault({ id: 'a', name: 'Alpha v2' })
    vault.syncWithVault({ id: 'b', name: 'Bravo' })

    expect(onItemAdded).toHaveBeenCalledTimes(2)
    expect(onItemAdded.mock.calls[0][0].id).toBe('a')
    expect(onItemAdded.mock.calls[1][0].id).toBe('b')
  })
})
