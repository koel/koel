import AlbumInfo from '@/components/main-wrapper/extra/album-info.vue'
import TrackListItem from '@/components/shared/track-list-item.vue'
import factory from '@/tests/factory'

describe('components/main-wrapper/extra/album-info', () => {
  it('displays the info as a sidebar by default', () => {
    const wrapper = shallow(AlbumInfo, {
      propsData: {
        album: factory('album')
      }
    })
    wrapper.findAll('#albumInfo.sidebar').should.have.lengthOf(1)
    wrapper.findAll('#albumInfo.full').should.have.lengthOf(0)
  })

  it('can display the info in full mode', () => {
    const wrapper = shallow(AlbumInfo, {
      propsData: {
        album: factory('album'),
        mode: 'full'
      }
    })
    wrapper.findAll('#albumInfo.sidebar').should.have.lengthOf(0)
    wrapper.findAll('#albumInfo.full').should.have.lengthOf(1)
  })

  it('triggers showing full wiki', () => {
    const album = factory('album')
    const wrapper = shallow(AlbumInfo, {
      propsData: { album }
    })
    wrapper.click('.wiki button.more')
    wrapper.html().should.contain(album.info.wiki.full)
  })

  it('lists the correct number of tracks', () => {
    const wrapper = mount(AlbumInfo, {
      propsData: {
        album: factory('album')
      }
    })
    wrapper.findAll(TrackListItem).should.have.lengthOf(2)
  })

  it('displays a message if the album has no info', () => {
    const wrapper = mount(AlbumInfo, {
      propsData: {
        album: factory('album', { info: null })
      }
    })
    wrapper.html().should.contain('No album information found.')
  })
})
