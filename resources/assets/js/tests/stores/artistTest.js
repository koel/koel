import { artistStore } from '../../stores'
import data from '../blobs/data'
const artists = data.artists

describe('stores/artist', () => {
  beforeEach(() => artistStore.init(_.cloneDeep(artists)))
  afterEach(() => artistStore.state.artists = [])

  describe('#init', () => {
    it('correctly gathers artists', () => {
      artistStore.state.artists.length.should.equal(5)
    })
  })

  describe('#byId', () => {
    it('correctly gets an artist by ID', () => {
      artistStore.byId(3).name.should.equal('All-4-One')
    })
  })

  describe('#compact', () => {
    it('correctly compact artists', () => {
      artistStore.compact()
      // because we've not processed songs/albums, all artists here have no songs
      // and should be removed after compact()ing
      artistStore.state.artists.should.be.empty
    })
  })
})
