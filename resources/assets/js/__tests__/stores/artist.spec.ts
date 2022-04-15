// @ts-nocheck
import { cloneDeep } from 'lodash'
import { artistStore } from '@/stores'
import data from '@/__tests__/blobs/data'

describe('stores/artist', () => {
  beforeEach(() => artistStore.init(cloneDeep(data.artists)))

  afterEach(() => (artistStore.state.artists = []))

  it('gathers artists', () => {
    expect(artistStore.state.artists).toHaveLength(5)
  })

  it('gets an artist by ID', () => {
    expect(artistStore.byId(3).name).toBe('All-4-One')
  })

  it('compact artists', () => {
    artistStore.compact()
    // because we've not processed songs/albums, all artists here have no songs
    // and should be removed after compacting
    expect(artistStore.state.artists).toHaveLength(0)
  })
})
