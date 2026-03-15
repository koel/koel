import 'fake-indexeddb/auto'
import { beforeEach, describe, expect, it } from 'vite-plus/test'
import { offlineManifest } from './offlineManifest'
import type { OfflineManifestEntry } from './offlineManifest'

describe('offlineManifest', () => {
  const makePlayable = (id: string): Playable =>
    ({
      id,
      type: 'songs',
      title: `Song ${id}`,
      length: 200,
      playback_state: 'Stopped',
    }) as unknown as Playable

  const makeEntry = (id: string): OfflineManifestEntry => ({
    playable: makePlayable(id),
    cachedAt: Date.now(),
    size: 1024,
  })

  beforeEach(async () => {
    await offlineManifest.clear()
  })

  it('stores and retrieves an entry', async () => {
    const entry = makeEntry('song-1')
    await offlineManifest.put(entry)

    const all = await offlineManifest.getAll()
    expect(all).toHaveLength(1)
    expect(all[0].playable.id).toBe('song-1')
  })

  it('returns empty array when no entries', async () => {
    const all = await offlineManifest.getAll()
    expect(all).toHaveLength(0)
  })

  it('retrieves all entries', async () => {
    await offlineManifest.put(makeEntry('song-1'))
    await offlineManifest.put(makeEntry('song-2'))
    await offlineManifest.put(makeEntry('song-3'))

    const all = await offlineManifest.getAll()
    expect(all).toHaveLength(3)
    expect(all.map(e => e.playable.id).sort((a, b) => a.localeCompare(b))).toEqual(['song-1', 'song-2', 'song-3'])
  })

  it('removes an entry', async () => {
    await offlineManifest.put(makeEntry('song-1'))
    await offlineManifest.remove('song-1')

    const all = await offlineManifest.getAll()
    expect(all).toHaveLength(0)
  })

  it('clears all entries', async () => {
    await offlineManifest.put(makeEntry('song-1'))
    await offlineManifest.put(makeEntry('song-2'))
    await offlineManifest.clear()

    const all = await offlineManifest.getAll()
    expect(all).toHaveLength(0)
  })

  it('overwrites existing entry with same playable id', async () => {
    await offlineManifest.put(makeEntry('song-1'))

    const updated = makeEntry('song-1')
    updated.playable.title = 'Updated Title'
    await offlineManifest.put(updated)

    const all = await offlineManifest.getAll()
    expect(all).toHaveLength(1)
    expect(all[0].playable.title).toBe('Updated Title')
  })
})
