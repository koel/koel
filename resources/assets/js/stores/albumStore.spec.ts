import { cloneDeep } from 'lodash'
import data from '@/__tests__/blobs/data'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { albumStore, artistStore } from '@/stores'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      artistStore.init(cloneDeep(data.artists))
      albumStore.init(cloneDeep(data.albums))
    })
  }

  protected afterEach () {
    super.afterEach(() => {
      artistStore.state.artists = []
      albumStore.state.albums = []
    })
  }

  protected test () {
    it('gathers albums', () => expect(albumStore.state.albums).toHaveLength(7))
    it('sets album artists', () => expect(albumStore.state.albums[0].artist.id).toBe(3))
    it('gets an album by ID', () => expect(albumStore.byId(1193).name).toBe('All-4-One'))

    it('compacts albums', () => {
      // because we've not processed songs, all albums here have no songs
      // and should be removed after compacting
      albumStore.compact()
      expect(albumStore.state.albums).toHaveLength(0)
    })

    it('returns all albums', () => expect(albumStore.all).toHaveLength(7))
  }
}
