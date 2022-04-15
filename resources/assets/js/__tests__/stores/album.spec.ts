// @ts-nocheck
import { cloneDeep } from 'lodash'
import { albumStore, artistStore } from '@/stores'
import data from '@/__tests__/blobs/data'

describe('stores/album', () => {
  beforeEach(() => {
    artistStore.init(cloneDeep(data.artists))
    albumStore.init(cloneDeep(data.albums))
  })

  afterEach(() => {
    artistStore.state.artists = []
    albumStore.state.albums = []
  })

  it('gathers albums', () => {
    expect(albumStore.state.albums).toHaveLength(7)
  })

  it('sets album artists', () => {
    expect(albumStore.state.albums[0].artist.id).toBe(3)
  })

  it('gets an album by ID', () => {
    expect(albumStore.byId(1193).name).toBe('All-4-One')
  })

  it('compacts albums', () => {
    albumStore.compact()
    expect(albumStore.state.albums).toHaveLength(0)
  })

  it('returns all albums', () => {
    expect(albumStore.all).toHaveLength(7)
  })
})
