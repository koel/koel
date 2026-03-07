import 'fake-indexeddb/auto'
import { beforeEach, describe, expect, it } from 'vitest'
import { offlineManifest } from './offlineManifest'
import type { OfflineManifestEntry } from './offlineManifest'

describe('offlineManifest', () => {
  const makeEntry = (songId: string): OfflineManifestEntry => ({
    songId,
    title: `Song ${songId}`,
    artist: 'Test Artist',
    album: 'Test Album',
    cachedAt: Date.now(),
    size: 1024,
  })

  beforeEach(async () => {
    await offlineManifest.clear()
  })

  it('stores and retrieves an entry', async () => {
    const entry = makeEntry('song-1')
    await offlineManifest.put(entry)

    const retrieved = await offlineManifest.get('song-1')
    expect(retrieved).toEqual(entry)
  })

  it('returns undefined for missing entry', async () => {
    const result = await offlineManifest.get('nonexistent')
    expect(result).toBeUndefined()
  })

  it('retrieves all entries', async () => {
    await offlineManifest.put(makeEntry('song-1'))
    await offlineManifest.put(makeEntry('song-2'))
    await offlineManifest.put(makeEntry('song-3'))

    const all = await offlineManifest.getAll()
    expect(all).toHaveLength(3)
    expect(all.map(e => e.songId).sort()).toEqual(['song-1', 'song-2', 'song-3'])
  })

  it('removes an entry', async () => {
    await offlineManifest.put(makeEntry('song-1'))
    await offlineManifest.remove('song-1')

    const result = await offlineManifest.get('song-1')
    expect(result).toBeUndefined()
  })

  it('clears all entries', async () => {
    await offlineManifest.put(makeEntry('song-1'))
    await offlineManifest.put(makeEntry('song-2'))
    await offlineManifest.clear()

    const all = await offlineManifest.getAll()
    expect(all).toHaveLength(0)
  })

  it('overwrites existing entry with same songId', async () => {
    await offlineManifest.put(makeEntry('song-1'))

    const updated: OfflineManifestEntry = {
      ...makeEntry('song-1'),
      title: 'Updated Title',
    }

    await offlineManifest.put(updated)

    const retrieved = await offlineManifest.get('song-1')
    expect(retrieved?.title).toBe('Updated Title')

    const all = await offlineManifest.getAll()
    expect(all).toHaveLength(1)
  })
})
