import ArtistInfo from '@/components/main-wrapper/extra/artist-info.vue'
import artist from '@/tests/blobs/artist'

describe('components/main-wrapper/extra/artist-info', () => {
    it('displays the info as a sidebar by default', () => {
    const wrapper = shallow(ArtistInfo, {
      propsData: {
        artist
      }
    })
    expect(wrapper.findAll('#artistInfo.sidebar')).toHaveLength(1)
    expect(wrapper.findAll('#artistInfo.full')).toHaveLength(0)
  })

  it('can display the info in full mode', () => {
    const wrapper = shallow(ArtistInfo, {
      propsData: {
        artist,
        mode: 'full'
      }
    })
    expect(wrapper.findAll('#artistInfo.sidebar')).toHaveLength(0)
    expect(wrapper.findAll('#artistInfo.full')).toHaveLength(1)
  })

  it('triggers showing full bio', () => {
    const wrapper = shallow(ArtistInfo, {
      propsData: {
        artist
      }
    })
    wrapper.find('.bio button.more').trigger('click')
    expect(wrapper.html()).toContain(artist.info.bio.full)
  })

  it('displays a message if the artist has no info', () => {
    const artistWithNoInfo = _.clone(artist)
    artistWithNoInfo.info = null
    const wrapper = mount(ArtistInfo, {
      propsData: {
        artist: artistWithNoInfo
      }
    })
    expect(wrapper.html()).toContain('Nothing can be found. This artist is a mystery.')
  })
})
