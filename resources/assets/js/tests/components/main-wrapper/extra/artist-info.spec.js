import ArtistInfo from '@/components/main-wrapper/extra/artist-info.vue'
import factory from '@/tests/factory'

describe('components/main-wrapper/extra/artist-info', () => {
    it('displays the info as a sidebar by default', () => {
    const wrapper = shallow(ArtistInfo, {
      propsData: {
        artist: factory('artist')
      }
    })
    wrapper.findAll('#artistInfo.sidebar').should.have.lengthOf(1)
    wrapper.findAll('#artistInfo.full').should.have.lengthOf(0)
  })

  it('can display the info in full mode', () => {
    const wrapper = shallow(ArtistInfo, {
      propsData: {
        artist: factory('artist'),
        mode: 'full'
      }
    })
    wrapper.findAll('#artistInfo.sidebar').should.have.lengthOf(0)
    wrapper.findAll('#artistInfo.full').should.have.lengthOf(1)
  })

  it('triggers showing full bio', () => {
    const artist = factory('artist')
    const wrapper = shallow(ArtistInfo, {
      propsData: { artist }
    })
    wrapper.click('.bio button.more')
    wrapper.html().should.contain(artist.info.bio.full)
  })

  it('displays a message if the artist has no info', () => {
    const wrapper = mount(ArtistInfo, {
      propsData: {
        artist: factory('artist', { info: null })
      }
    })
    wrapper.html().should.contain('Nothing can be found. This artist is a mystery.')
  })
})
