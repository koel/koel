import { cloneDeep } from 'lodash'
import data from '@/__tests__/blobs/data'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { artistStore } from '@/stores'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => artistStore.init(cloneDeep(data.artists)))
  }

  protected afterEach () {
    super.afterEach(() => (artistStore.state.artists = []))
  }

  protected test () {
    it('gathers artists', () => expect(artistStore.state.artists).toHaveLength(5))
    it('gets an artist by ID', () => expect(artistStore.byId(3)!.name).toBe('All-4-One'))

    it('compact artists', () => {
      artistStore.compact()
      // because we've not processed songs/albums, all artists here have no songs
      // and should be removed after compacting
      expect(artistStore.state.artists).toHaveLength(0)
    })
  }
}
