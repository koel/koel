import ArtistInfo from '@/components/main-wrapper/extra/artist-info.vue'
import artist from '@/tests/blobs/artist'

describe('components/main-wrapper/extra/artist-info', () => {
    it('displays the info as a sidebar by default', () => {
    const wrapper = shallow(ArtistInfo, {
      propsData: { artist }
    })
    wrapper.findAll('#artistInfo.sidebar').should.have.lengthOf(1)
    wrapper.findAll('#artistInfo.full').should.have.lengthOf(0)
  })

  it('can display the info in full mode', () => {
    const wrapper = shallow(ArtistInfo, {
      propsData: {
        artist,
        mode: 'full'
      }
    })
    wrapper.findAll('#artistInfo.sidebar').should.have.lengthOf(0)
    wrapper.findAll('#artistInfo.full').should.have.lengthOf(1)
  })

  it('triggers showing full bio', () => {
    const wrapper = shallow(ArtistInfo, {
      propsData: { artist }
    })
    wrapper.find('.bio button.more').trigger('click')
    wrapper.html().should.contain(artist.info.bio.full)
  })

  it('displays a message if the artist has no info', () => {
    const artistWithNoInfo = _.clone(artist)
    artistWithNoInfo.info = null
    const wrapper = mount(ArtistInfo, {
      propsData: {
        artist: artistWithNoInfo
      }
    })
    wrapper.html().should.contain('Nothing can be found. This artist is a mystery.')
  })
})
