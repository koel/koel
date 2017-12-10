import { albumStore, artistStore } from '../../stores'
import data from '../blobs/data'

const { artists, albums } = data

describe('stores/album', () => {
  beforeEach(() => {
    artistStore.init(_.cloneDeep(artists))
    albumStore.init(_.cloneDeep(albums))
  })

  afterEach(() => {
    artistStore.state.artists = []
    albumStore.state.albums = []
  })

  describe('#init', () => {
    it('correctly gathers albums', () => {
      albumStore.state.albums.length.should.equal(7)
    })

    it('correctly sets album artists', () => {
      albumStore.state.albums[0].artist.id.should.equal(3)
    })
  })

  describe('#byId', () => {
    it('correctly gets an album by ID', () => {
      albumStore.byId(1193).name.should.equal('All-4-One')
    })
  })

  describe('#compact', () => {
    it('correctly compacts albums', () => {
      albumStore.compact()
      albumStore.state.albums.should.be.empty
    })
  })

  describe('#all', () => {
    it('correctly returns all albums', () => {
      albumStore.all.length.should.equal(7)
    })
  })
})
