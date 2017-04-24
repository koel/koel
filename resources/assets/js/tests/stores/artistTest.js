require('chai').should()
import { cloneDeep, last } from 'lodash'

import { artistStore } from '../../stores'
import { default as artists, singleAlbum, singleArtist } from '../blobs/media'

describe('stores/artist', () => {
  beforeEach(() => artistStore.init(cloneDeep(artists)))
  afterEach(() => artistStore.state.artists = [])

  describe('#init', () => {
    it('correctly gathers artists', () => {
      artistStore.state.artists.length.should.equal(3)
    })

    it('correctly gets artist images', () => {
      artistStore.state.artists[0].image.should.equal('/public/img/covers/565c0f7067425.jpeg')
    })

    it('correctly counts songs by artists', () => {
      artistStore.state.artists[0].songCount = 3
    })
  })

  describe('#getImage', () => {
    it('correctly gets an artist’s image', () => {
      artistStore.getImage(artistStore.state.artists[0]).should.equal('/public/img/covers/565c0f7067425.jpeg')
    })
  })

  describe('#add', () => {
    beforeEach(() => artistStore.add(cloneDeep(singleArtist)))

    it('correctly adds an artist', () => {
      last(artistStore.state.artists).name.should.equal('John Cena')
    })
  })

  describe('#remove', () => {
    beforeEach(() => artistStore.remove(artistStore.state.artists[0]))

    it('correctly removes an artist', () => {
      artistStore.state.artists.length.should.equal(2)
      artistStore.state.artists[0].name.should.equal('Bob Dylan')
    })
  })
})
